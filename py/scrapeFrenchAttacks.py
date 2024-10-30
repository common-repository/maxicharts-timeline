#!/usr/bin/python2.4
# -*- coding: utf-8 -*-
# coding: utf8

import requests
import pandas as pd
from bs4 import BeautifulSoup
import datetime
#from dateutil import parser
import dateparser
from arrow import *


website_url = requests.get('https://fr.wikipedia.org/wiki/Chronologie_des_actes_terroristes_en_France'.encode(encoding='utf_8')).text

# use other lib in js directly ; https://medium.com/data-scraper-tips-tricks/scraping-data-with-javascript-in-3-minutes-8a7cf8275b31

soup = BeautifulSoup(website_url,'lxml')

#print(soup.prettify())

OUTPUT_DIR = './data/'
OUTPUT_FILE = 'french_attacks'
OUTPUT_EXT = '.csv'
COMPLETE_OUTPUT_FILENAME = OUTPUT_DIR + OUTPUT_FILE + OUTPUT_EXT
OUTPUT_FILE_ENCODING = 'latin1'
OUTPUT_FILE_ENCODING = 'utf8'


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
    
    '''
    
    twoDatesPos = dateBlock.find('-')
    twoDatesPos2 = dateBlock.find('/')
    if twoDatesPos >= 0:
        splitStart = dateBlock.split('-')
        print "- found "
        print splitStart
        st = validate(splitStart[0])
        ed = validate(splitStart[1])
        if st:
            start = st.strftime('%Y-%m-%d')
        if ed:
            end = ed.strftime('%Y-%m-%d')
    elif twoDatesPos2 >= 0:
        splitStart = dateBlock.split('/')
        print "/ found "
        print splitStart
        st = validate(splitStart[0])
        ed = validate(splitStart[1])
        if st:
            start = st.strftime('%Y-%m-%d')
        if ed:
            end = ed.strftime('%Y-%m-%d')
    else:'''
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

items = soup.findAll('mw-headline li')
totalItems = str(len(items))
print (totalItems+' li found ---------------------------')

df = pd.DataFrame()

idx = 1
for item in items:
    idx += 1
    print (str(idx)+" / "+totalItems+") ")
    #print item
    value = item.text
    #htmlContent = item
    print (item)
    print (value)
    # 1941 : Le 
    # 1947 : l’électronique, le transistor
    # 1988 : la pilule abortive RU486 Étienne-Émile Baulieu France
    pattern = u" \u003A " 
    patternPos = value.find(pattern)
    '''
    import re
    splittedHtml = item.split(text=' : ')
    if splittedHtml:
        print splittedHtml
    '''
    pattern2 = u"\u00A0\u003A "
    patternPos2 = value.find(pattern2)
    print (str(patternPos) +' '+str(patternPos2))
    if patternPos >= 0:
        splittedEvent = value.split(pattern)
    elif patternPos2 >= 0:
        splittedEvent = value.split(pattern2)
    else:
        continue   
    
    
    dateBlock = splittedEvent[0].strip()
    start, end = getStartEndDates(dateBlock)
    
    
    print (splittedEvent[1])
    soupContent = BeautifulSoup(splittedEvent[1], "lxml")
    print (soupContent)
    firstLink = soupContent.find('a')
    print (firstLink)
    if firstLink and len(firstLink.text) >= 3:
        title = firstLink.contents        
    else:
        title = splittedEvent[1].strip()[:25]
    description = '<div>'+start + ' : '+splittedEvent[1].strip()+'</div>'
        
    if start:
        if not end or len(end) == 0:
            newItem = {'start':start,'content':title,'title':description}
        else:
            newItem = {'start':start,'end':end,'content':title,'title':description}
        
        print ("------------------")
        print (newItem)
        print ("------------------")
        df = df.append(newItem, ignore_index=True)
    
    
    
# add other wiki sources to df
#https://fr.wikipedia.org/wiki/Chronologie_des_attentats_en_France_en_2015
def scrapeAttacksDetailsForYear(url,year,dataframe):
    website_url = requests.get(url.encode(encoding='utf_8')).text
    soup = BeautifulSoup(website_url,'lxml')
    month = soup.findAll('span',{'class':'mw-headline'})
    My_tables = soup.findAll('table',{'class':'wikitable'})
    print (str(len(My_tables))+' table found ---------------------------')
    idx = 0
    currentYear = year
    for table in My_tables:
        lines = table.findAll('tr')
        print (str(len(lines))+' tr rows found in table')
        currentMonth = month[idx].text
        print (currentMonth)
        for line in lines:
            print ("-----------------------------------")
            if not line.find('td'):
                continue
            
            td_list = line.findAll('td')
            #print td_list
            print (str(len(td_list))+' columns found')
            #print td_list[0].text.strip() + ' '+currentYear
            dateBlock = td_list[0].text.strip() +'  '+currentYear
            
            #dateBlock = splittedEvent[0].strip()
            start, end = getStartEndDates(dateBlock)
            '''
            dt = dateparser.parse(start_date, languages=['fr'])
            if dt:
                start = dt.strftime('%Y-%m-%d')
                print start
            else:
                continue
            end = ''
            '''
            print (start + " <--> "+end)
            
            title = td_list[1].text.strip()[:25]
            try:
                nb_victims = str(int(td_list[2].text) + int(td_list[3].text))
            except:
                nb_victims = '?'
                
            try:
                description = start + ' : ' + td_list[4].text
            except IndexError:
                description = start

            print (description)
            if start:
                
                if not end or len(end) == 0:
                    newItem = {'start':start,'content':title,'title':description,'group':nb_victims}
                else:
                    newItem = {'start':start,'end':end,'content':title,'title':description,'group':nb_victims}
                    
                print ("------------------")
                print (newItem)
                print ("------------------")
                dataframe = dataframe.append(newItem, ignore_index=True)
           
    
        idx += 1
    return dataframe

df = scrapeAttacksDetailsForYear('https://fr.wikipedia.org/wiki/Chronologie_des_attentats_en_France_en_2015','2015',df)
df = scrapeAttacksDetailsForYear('https://fr.wikipedia.org/wiki/Chronologie_des_attentats_en_France_en_2016','2016',df)
df = scrapeAttacksDetailsForYear('https://fr.wikipedia.org/wiki/Chronologie_des_attentats_en_France_en_2017','2017',df)
df = scrapeAttacksDetailsForYear('https://fr.wikipedia.org/wiki/Terrorisme_en_France_en_2018','2018',df)

print(df)
print(COMPLETE_OUTPUT_FILENAME)
df.to_csv(path_or_buf=COMPLETE_OUTPUT_FILENAME, sep=';',encoding=OUTPUT_FILE_ENCODING, index=False)
