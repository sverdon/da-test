import sys
sys.path.append("/home/customer/lib")
import pandas as pd
import pymysql
from sqlalchemy import create_engine
import numpy as np
""" import mysql.connector as mysql

conn = mysql.connect(user ='u4nb3xb15qjtk', password = 'wgb)@ir$cC23', host = '35.208.174.209', database = 'dbthezxpnokgxv')

if (conn.is_connected()):
    print("Connected")
else:
    print("Not connected")  """
""" 
user = 'u4nb3xb15qjtk' #'root'
password = 'wgb)@ir$cC23' #'S&ADelAgua'  #password with '@' in it causes problems
host = '35.208.174.209' #'127.0.0.1'
port = 3306
database = 'dbthezxpnokgxv' #'testdb' """

host = "35.208.174.209"
user = "uenze6vve1gmk"
password = "schlafen123"
database = "dbthezxpnokgxv"
port = 3306
conn = create_engine("mysql+pymysql://{0}:{1}@{2}:{3}/{4}".format(
            user, password, host, port, database))
if(conn.connect):
    print("Connected")
else:
    print("Not connected")

df_FieldColumns = pd.read_sql_query("SELECT * FROM frm_FormVersionFields WHERE DSID = 34", conn)
#print(df_FieldColumns)

SourceHeaders = df_FieldColumns['FieldName_SourceFile'].tolist()
print(SourceHeaders)

data = pd.read_csv('dataCalc.csv', usecols=SourceHeaders)
data = data.fillna(0)
print(data)


""" Headers = ['vs49a1.WET SEASON', 'vs49a1.DRY SEASON', 'vs49a2.WET SEASON', 'vs49a2.DRY SEASON', 'vs49a4.WET SEASON', 'vs49a4.DRY SEASON', 'vs50.vs50a1', 'vs78.vs78a1']
df = pd.read_csv('dataCalc.csv', usecols=Headers)
df = df.fillna(0)
#SystemID = df['System ID']
#print(SystemID)
#print(df)

fractionWet = []
fractionDry = []
potSkirt = []
uptime = []
rows = df.shape[0]
for i in range(rows):
    if(df['vs49a4.WET SEASON'].values[i] == 0):
        fractionWet.append(0)
    else:
        fractionWet.append(df['vs49a2.WET SEASON'].values[i] / df['vs49a4.WET SEASON'].values[i])

    #print(fractionWet)

    if(df['vs49a4.DRY SEASON'].values[i] == 0):
        fractionDry.append(0)
    else:
        fractionDry.append(df['vs49a2.DRY SEASON'].values[i] / df['vs49a4.DRY SEASON'].values[i])


    min = 0
    if(df['vs49a4.WET SEASON'].values[i] == 0):
        potSkirt.append(-1)
    else:
        min = (df['vs50.vs50a1'].values[i] / df['vs49a4.WET SEASON'].values[i])
        if(min < 1):
            potSkirt.append(min)
        else:
            potSkirt.append(1)

    uptime.append(1004-df['vs78.vs78a1'].values[i])
    #print (fractionDry) 

df.insert(loc = 7, column = 'fractionWet', value = fractionWet)
df.insert(loc = 8, column = 'fractionDry', value = fractionDry)
df.insert(loc = 9, column = 'potSkirt', value = potSkirt)
df.insert(loc = 10, column ='Uptime', value = uptime)
print(df)

df.columns = ['notDuraWet', 'notDuraDry', 'DuraWet', 'DuraDry', 'TotalWkCookWet', 'TotalWkCookDry', 'PotSkirt', 'FracTotalCookWet', 'FracTotalCookDry', 'PotSkirtUsage', 'Uptime', 'KB4']

print(df)

df.to_sql('datacalc2', conn, schema = 'testdb', if_exists = 'append', index = False)
conn.execute("SELECT * FROM testdb.dataCalc").fetchall()

# if(df.to_sql('testdb.datacalc', conn, if_exists = 'append', index = False) == 0): #index_label = 'dataCalcID') == 0):
#     print('No rows affected') """

#df1 = pd.read_sql_table('datacalc2', conn)

