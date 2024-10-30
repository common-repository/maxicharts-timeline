#!/usr/bin/python2.4
# -*- coding: utf-8 -*-
# coding: utf8

import requests
import pandas as pd
from bs4 import BeautifulSoup

#website_url = requests.get('https://fr.wikipedia.org/wiki/P%C3%A9riode_(g%C3%A9ologie)').text
#website_url = requests.get('https://fr.wikipedia.org/wiki/P&eacute;riode_(g&eacute;ologie)').text
website_url = requests.get('https://fr.wikipedia.org/wiki/P%C3%A9riode_(g%C3%A9ologie)'.encode(encoding='utf_8')).text

# utiliser plutot
# https://fr.wikipedia.org/wiki/%C3%89chelle_des_temps_g%C3%A9ologiques#Tableau_de_l'%C3%A9chelle_des_temps_g%C3%A9ologiques

#u"Grønlandsleiret, Oslo, Norway".encode('UTF-8')
soup = BeautifulSoup(website_url,'lxml')

#print(soup.prettify())

OUTPUT_DIR = './data/'
OUTPUT_FILE = 'geo_periods'
OUTPUT_EXT = '.csv'
COMPLETE_OUTPUT_FILENAME = OUTPUT_DIR + OUTPUT_FILE + OUTPUT_EXT
OUTPUT_FILE_ENCODING = 'latin1'

#My_div = soup.find('div',{'class':'infobox_v3'})
# #mw-content-text > div > div.infobox_v3 > table:nth-child(6)
My_tables = soup.findAll('table',{'class':'wikitable'})
print (str(len(My_tables))+' table found ---------------------------')

df = pd.DataFrame()
#firstLevelPeriods = []
#My_table
for table in My_tables:
    lines = table.findAll('tr')
    for line in lines:
        #print str(len(firstLevelPeriods))+'  ---------------------------'
        #print line
       
        if not line.find('td'):
            continue
        
        td_list = line.findAll('td')
        print (td_list)
        print (str(len(td_list))+' td found')
        if len(td_list) < 3:
            continue
        
        if len(td_list) == 5:
            eon = td_list[0].text.strip()
            ere = td_list[1].text.strip()
            name = td_list[2].text.strip()
            period = td_list[3].text.strip()
        elif len(td_list) == 4:
            ere = td_list[1].text.strip()
            name = td_list[1].text.strip()
            period = td_list[2].text.strip()
        elif len(td_list) == 3:
            name = td_list[0].text.strip()
            period = td_list[1].text.strip()
        
        '''
        name = name.decode('latin1').encode(encoding='utf_8')
        period = period.decode('latin1').encode(encoding='utf_8')
        '''
        
        print ('--------- RAW DATA ---------')
        print (name)
        print (period)     
        '''
        split_character = '&#45;'
        split_character = u'U+2014'.encode(encoding='utf_8')
        split_character = u'U+2013'
        split_character = '-' # hyphen
        '''
        #split_character = '–'.encode(encoding='utf_8') # dash
        split_character = u'\u2013'
        splitted_dates = period.split(split_character)
        print (splitted_dates)
        
        print (splitted_dates[0])
        print (splitted_dates[1])
        
        print ('----------------------------')
        if len(splitted_dates) != 2:
            continue
        
        
        start = '-'+splitted_dates[0]
        end = '-'+splitted_dates[1]
        
        newItem = {'start':start,'end':end,'content':name}
        print (newItem)
        #firstLevelPeriods.append(newItem)
        df = df.append(newItem, ignore_index=True)
        


#print(firstLevelPeriods)

print(df)
print(COMPLETE_OUTPUT_FILENAME)
df.to_csv(path_or_buf=COMPLETE_OUTPUT_FILENAME, sep=';',encoding=OUTPUT_FILE_ENCODING, index=False)
