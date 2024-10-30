#!/usr/bin/python2.4
# -*- coding: utf-8 -*-
# coding: utf8

import requests
import pandas as pd
from bs4 import BeautifulSoup

#website_url = requests.get('https://fr.wikipedia.org/wiki/P%C3%A9riode_(g%C3%A9ologie)').text
#website_url = requests.get('https://fr.wikipedia.org/wiki/P&eacute;riode_(g&eacute;ologie)').text
website_url = requests.get('https://fr.wikipedia.org/wiki/Chronologie_de_l%27histoire_des_techniques'.encode(encoding='utf_8')).text
#u"Grønlandsleiret, Oslo, Norway".encode('UTF-8')
soup = BeautifulSoup(website_url,'lxml')

#print(soup.prettify())

OUTPUT_DIR = './data/'
OUTPUT_FILE = 'techniques_history'
OUTPUT_EXT = '.csv'
COMPLETE_OUTPUT_FILENAME = OUTPUT_DIR + OUTPUT_FILE + OUTPUT_EXT
OUTPUT_FILE_ENCODING = 'latin1'
OUTPUT_FILE_ENCODING = 'utf8'

#My_div = soup.find('div',{'class':'infobox_v3'})
# #mw-content-text > div > div.infobox_v3 > table:nth-child(6)
items = soup.findAll('li')
totalItems = str(len(items))
print totalItems+' li found ---------------------------'

df = pd.DataFrame()
#firstLevelPeriods = []
#My_table
idx = 1
for item in items:
    idx += 1
    print str(idx)+" / "+totalItems+") "
    #print item
    value = item.text
    print value
    # 1941 : Le 
    # 1947 : l’électronique, le transistor
    # 1988 : la pilule abortive RU486 Étienne-Émile Baulieu France
    pattern = u" \u003A " 
    patternPos = value.find(pattern)
    
    pattern2 = u"\u00A0\u003A "
    patternPos2 = value.find(pattern2)
    print str(patternPos) +' '+str(patternPos2)
    if patternPos >= 0:
        splittedEvent = value.split(pattern)
    elif patternPos2 >= 0:
        splittedEvent = value.split(pattern2)
    else:
        continue   
    
    start = splittedEvent[0].strip()
    end = ''
    title = splittedEvent[1].strip()[:25]
    description = start + ' : '+splittedEvent[1].strip()
        
    if not end:
        newItem = {'start':start,'content':title,'title':description}
    else:
        newItem = {'start':start,'end':end,'content':title,'title':description}
    print "------------------"
    print newItem
    print "------------------"
    df = df.append(newItem, ignore_index=True)
    

print(df)
print(COMPLETE_OUTPUT_FILENAME)
df.to_csv(path_or_buf=COMPLETE_OUTPUT_FILENAME, sep=';',encoding=OUTPUT_FILE_ENCODING, index=False)
