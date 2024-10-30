#!/usr/bin/python2.4
# -*- coding: utf-8 -*-
# coding: utf8

import requests
import pandas as pd
from bs4 import BeautifulSoup
import datetime
import string
#from dateutil import parser
import dateparser
from arrow import *
import sys, re

def validate(date_text):
    try:
        #datetime.datetime.strptime(date_text, '%d %B %Y')
        dt = dateparser.parse(date_text, languages=['fr'])
        #dt = arrow.get(date_text)
        return dt
    except ValueError:
        raise ValueError("Incorrect data format")
    #except ParserError as p:
    #    raise ValueError("Incorrect data format")
        

def getStartEndDates(dateBlock):
    end = start = ''
    
    print (dateBlock)
    
    if 1:
        try:
            validDate = validate(dateBlock)
            if validDate:
                start = validDate.strftime('%Y-%m-%d')
            else:
                print ("none returned by validation")
        except ValueError as e:
            print (e)
            #continue
           
           
    print ("found dates : "+start+" <-> "+end)
    if not end:
        end = ''
         
    return start, end

def scrapeURL(url,dataframe):
    resp = requests.get(url.encode(encoding='utf_8'))
    #soup = BeautifulSoup(website_url,'lxml')
    #month = soup.findAll('span',{'class':'mw-headline'})
    
    from dateparser.search import search_dates
    #allDatesFound = search_dates(page_content)

    soup = BeautifulSoup(resp.content,'lxml')
 
    # search for all paragraphs on the webpage 
    paragraphs = soup.find_all("p")
 
    # get the text for each paragraph
    text = [x.text for x in paragraphs]
     
    # scrape any dates from each paragraph
    allDatesFound = []
    for paragraph in text:
        print(paragraph)
        try:
            
            allDatesFound.append([search_dates(paragraph),paragraph])
        except Exception:
            pass
        
        break
    
    print(allDatesFound)
    
    for occurenceFound in allDatesFound:
        print(occurenceFound)
        print(occurenceFound[0])
        #print(occurenceFound[1])
        
        (title,dateObj) = occurenceFound[0][0]
        end = ''
            
        print(title)
        print(dateObj)
        
        try:
            validDate = validate(str(dateObj))
            if validDate:
                start = validDate.strftime('%Y-%m-%d')
            else:
                print ("none returned by validation")
        except ValueError as e:
            print (e)
        
            
        paragraph = occurenceFound[1]
        sentences = re.findall(r"([^.]*?"+title+"[^.]*\.)",paragraph)
        description = start + ' : '+sentences[0]
        if start:        
            if not end or len(end) == 0:
                newItem = {'start':start,'content':title,'title':description}
            else:
                newItem = {'start':start,'end':end,'content':title,'title':description}
                
            print ("------------------")
            print (newItem)
            print ("------------------")
            dataframe = dataframe.append(newItem, ignore_index=True)    
            
    
   
    return dataframe
    

def format_filename(s):
    """Take a string and return a valid filename constructed from the string.
Uses a whitelist approach: any characters not present in valid_chars are
removed. Also spaces are replaced with underscores.
 
Note: this method may produce invalid filenames such as ``, `.` or `..`
When I use this method I prepend a date string like '2009_01_15_19_46_32_'
and append a file extension like '.txt', so I avoid the potential of using
an invalid filename.
 
"""
    valid_chars = "-_.() %s%s" % (string.ascii_letters, string.digits)
    filename = ''.join(c for c in s if c in valid_chars)
    filename = filename.replace(' ','_') # I don't like spaces in filenames.
    return filename

def main():
    input = sys.argv[1]

    import base64
    #urlAsFilename = base64.urlsafe_b64encode(input)
    urlAsFilename = format_filename(input)
    

    print(urlAsFilename)

    OUTPUT_DIR = './data/'
    OUTPUT_FILE = str(urlAsFilename)
    OUTPUT_EXT = '.csv'
    COMPLETE_OUTPUT_FILENAME = OUTPUT_DIR + OUTPUT_FILE + OUTPUT_EXT
    OUTPUT_FILE_ENCODING = 'latin1'
    OUTPUT_FILE_ENCODING = 'utf8'   
    
    df = pd.DataFrame()
   
    df = scrapeURL(input,df)
    
    print(df)
    print(COMPLETE_OUTPUT_FILENAME)
    
    written_path = df.to_csv(path_or_buf=COMPLETE_OUTPUT_FILENAME, sep=';',encoding=OUTPUT_FILE_ENCODING, index=False)
    print(written_path)
    return written_path


if __name__== "__main__":
  main()