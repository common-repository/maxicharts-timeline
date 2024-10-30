#!/usr/bin/python2.4
# -*- coding: utf-8 -*-
# coding: utf8

import requests
website_url = requests.get('https://fr.wikipedia.org/wiki/Histoire_de_France').text

from bs4 import BeautifulSoup
soup = BeautifulSoup(website_url,'lxml')
#print(soup.prettify())


OUTPUT_DIR = './data/'
OUTPUT_FILE = 'french_story'
OUTPUT_EXT = '.csv'
COMPLETE_OUTPUT_FILENAME = OUTPUT_DIR + OUTPUT_FILE + OUTPUT_EXT
OUTPUT_FILE_ENCODING = 'latin1'

#My_div = soup.find('div',{'class':'infobox_v3'})
# #mw-content-text > div > div.infobox_v3 > table:nth-child(6)
My_tables = soup.findAll('table')

import pandas as pd
df = pd.DataFrame()

for table in My_tables:
    lines = table.findAll('tr')
    for line in lines:
        print str(len(firstLevelPeriods))+'  ---------------------------'
        print line
        #if line.find_next_sibling("th") and line.find_next_sibling('td'):
        th =  line.find("th")
        if not th:
            continue
        periods = line.find("th").findAll('a')
        if not periods:
            continue
       
        if not line.find('td'):
            continue
        name = line.find('td').find('a').text
        print 'pppppppppppppp'
        print periods
        print 'pppppppppppppp'
        
        if len(periods) < 2:
            continue
        
        print periods[0]
        print periods[1]
        print name
        
        newItem = {'start':periods[0].text,'end':periods[1].text,'content':name}
        print newItem
        #firstLevelPeriods.append(newItem)
        df = df.append(newItem, ignore_index=True)
        


#print(firstLevelPeriods)

print(df)
print(COMPLETE_OUTPUT_FILENAME)
df.to_csv(path_or_buf=COMPLETE_OUTPUT_FILENAME, sep=';',encoding=OUTPUT_FILE_ENCODING, index=False)
