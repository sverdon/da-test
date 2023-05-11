import sys
sys.path.append("/home/customer/lib")
import glob 
import os
import pandas as pd
import pymysql
from sqlalchemy import create_engine, text
import numpy as np
from scipy import stats as st
from pathlib import Path
import csv

"""
user = 'u4nb3xb15qjtk' #'root'
password = 'wgb)@ir$cC23' #'S&ADelAgua'  #password with '@' in it causes problems
host = '35.208.174.209' #'127.0.0.1'
port = 3306
database = 'dbthezxpnokgxv' #'testdb' 
"""

if __name__ == "__main__":

    list_of_files = glob.iglob('/home/customer/www/dash-delagua.com/public_html/da-forms/worldapp-sync/DataCalc/*.csv') 
    oldest_file = min(list_of_files, key=os.path.getctime)
    print(os.path.basename(oldest_file))

    #sys.exit()

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

    df_FieldColumns = pd.read_sql_query("SELECT * FROM frm_FormVersionFields WHERE DSID = 34 Order By `Order`;", conn)
    #print(df_FieldColumns)

    SourceHeaders = df_FieldColumns['FieldName_SourceFile'].tolist() #save column headers from CSV as a list
    TableHeaders = df_FieldColumns['FieldName_DBtbl'].tolist() #save columns names in table as list
    #print(SourceHeaders)
    #print(TableHeaders)

    data = pd.read_csv('ExportToCSV41636330_scenario1.csv', usecols=SourceHeaders) #read csv, only saving data in with headers in the SourceHeaders list
    data = data.fillna(0)
    data.columns = TableHeaders
    print(data)

     #new columns to calc and add
    UsageFrac_Wet = []
    UsageFrac_Dry = []
    USkirt_Wet = []
    USkirt_Dry = []
    uptime = []
    rows = data.shape[0]
    for i in range(rows):
        if(data['CEWeek_Wet_Total'].values[i] == 0):
            UsageFrac_Wet.append(0)
        else:
            UsageFrac_Wet.append(data['CEWeek_Wet_DA'].values[i] / data['CEWeek_Wet_Total'].values[i])

        #print(UsageFrac_Wet)

        if(data['CEWeek_Dry_Total'].values[i] == 0):
            UsageFrac_Dry.append(0)
        else:
            UsageFrac_Dry.append(data['CEWeek_Dry_DA'].values[i] / data['CEWeek_Dry_Total'].values[i])


        #calc USkirt_Wet
        min = 0
        if(data['CEWeek_Wet_DA'].values[i] == 0):
            USkirt_Wet.append(-1)
        else:
            min = (data['PSWeek_Wet'].values[i] / data['CEWeek_Wet_DA'].values[i])
            if(min < 1):
                USkirt_Wet.append(min)
            else:
                USkirt_Wet.append(1)

        #calc USkirt_Dry
        if(data['CEWeek_Dry_DA'].values[i] == 0):
            USkirt_Dry.append(-1)
        else:
            min = (data['PSWeek_Dry'].values[i] / data['CEWeek_Dry_DA'].values[i])
            if(min < 1):
                USkirt_Dry.append(min)
            else:
                USkirt_Dry.append(1)

        #uptime.append(1004-data['Downtime'].values[i])
        #print (UsageFrac_Dry) 

    data.insert(loc = 10, column = 'UsageFrac_Wet', value = UsageFrac_Wet)
    data.insert(loc = 11, column = 'UsageFrac_Dry', value = UsageFrac_Dry)
    data.insert(loc = 12, column = 'USkirt_Wet', value = USkirt_Wet)
    data.insert(loc = 13, column = 'USkirt_Dry', value = USkirt_Dry)
    #data.insert(loc = 14, column ='Uptime', value = uptime)
    print(data)

    #data.to_sql('fd_MonitoringData', conn, schema = 'dbthezxpnokgxv', if_exists = 'append', index = False)

    #need to rename so column name matches what is in SurveyParams table
    data.rename({'HHsize_Total':'HHsize', 'isTNSAvail':'OC_Stoves'},axis=1, inplace=True)
    #print(data)

    #write out column headers to csv
    # header = ['ParamID', 'Quartile_1', 'Quartile_3', 'IQR', 'iqr_Min', 'iqr_Max', 'Average', 'Average_iqr', 'SD', 'TotalCount', 'Prop_Count', 'Proportion', 'CI', 'UorL_Bound', 'Reliability', 'Result']
    # f = open('results2.csv','w',newline='')
    # write = csv.writer(f)
    # write.writerow(header)
    # f.close()

    #create dict of parameters and type
    params = {} 
    sql = "SELECT * FROM dbthezxpnokgxv.c_SurveyedParams;"
    result = conn.execute(sql).fetchall()
    for row in result:
        paramName = row[1]
        paramType = row[2]
        params[paramName] = paramType

    print(params)

    #get GroupID and MPVID pairing
    pairs = {}
    sql = "SELECT MPVID, GroupID FROM dbthezxpnokgxv.c_MonitoringResults WHERE MPVID is not null and GroupID is not null;"
    result = conn.execute(sql).fetchall()
    for row in result:
        search = row
        if(row not in pairs):
            pairs.append(row)

    print(pairs)

    #variables for calculations
    Quar1 = Quar3 = iqr = max = 0
    straightMean = trimmedMean = stnd_deviation = confid_lvl = UorL_Bound = 0
    totalCount = proportion = proportionCount = reliability = parameter_value = 0

    #for each MPVID and GroupID pairing, do the calculations and update MonitoringResults table
    for list in pairs: 
        mpvid = list[0]
        groupID = list[1]
        values = {'mpvid':list[0], 'groupID':list[1]} #create dictionary
        print(values)

        #get rows that have the same MPVID and GroupID
        sql = text("SELECT * FROM dbthezxpnokgxv.fd_MonitoringData WHERE MPVID = :mpvid and GroupID = :groupID")
        
        selectData = pd.read_sql(sql, conn, params=values) #store results into new dataframe

        #need to rename so column name matches what is in SurveyParams table
        selectData.rename({'HHsize_Total':'HHsize', 'isTNSAvail':'OC_Stoves'},axis=1, inplace=True)

        selectData.drop(columns=['Link_FormResponse', 'Timestamp'], inplace=True) #drop these columns - don't need for the calculations
        #print(selectData)


        for column in selectData:
            flag = 1 #used to mark if update to table should happen
            paramID = 0
            if(params.get(column, "NULL") == 'Mean'):
            #if(column == 'HHsize' or column == 'UsageFrac_Wet' or column == 'UsageFrac_Dry' or column == 'USkirt_Wet' or column == 'USkirt_Dry'):
                
                print(column)
                #print(data[column].describe())
                sql = "SELECT * FROM dbthezxpnokgxv.c_SurveyedParams WHERE ParameterName = '" + column + "';"
                result = conn.execute(sql).fetchall()
                for row in result:
                    #print(row)
                    paramID = row[0]

                Quar1 = np.quantile(selectData[column], 0.25)
                print("Quar1: ", Quar1, end =" ")
                Quar3 = np.quantile(selectData[column], 0.75)
                print("Quar3: ",Quar3, end =" ")
                iqr = Quar3 - Quar1
                print("IQR: ", iqr, end =" ")
                max = (1.5 * iqr) + Quar3
                print("Max: ", max, end =" ") 

                #Straight Mean: Average of column data
                straightMean = selectData[column].mean()
                print("Straight mean - Average of all data: ", straightMean)

                #extract column and get rid of values outside of min-max
                trimmed = selectData[column] 
                trimmed = trimmed.fillna(0)
                # print("column as list")
                # print(trimmed)
                trimmed = trimmed[(trimmed >= 0) & (trimmed <= max)] #get rid of values outside of range min-max
                # print("Data with only values between max and min, inclusive")
                #print(trimmed) 

                #Trimmed Mean: Average of all data that is between Min-Max
                trimmedMean = np.nanmean(trimmed) #trimmed.mean()
                print("Trimmed mean - Average of all data between max and min: ", trimmedMean)

                #Standard Deviation of all data that's between Min-Max
                stnd_deviation = np.std(trimmed, ddof=1) #trimmed.std() #standard deviation
                print("Standard deviation of all data between max and min: ", stnd_deviation)
                
                #Total Count - Mean parameters: num. of responses that are between min-max
                totalCount = trimmed.count()
                print("Total number of responses between max and min: ", totalCount)
                
                #95% Confidence Interval
                if(trimmedMean != 0 and stnd_deviation != 0):
                    confidRange = st.norm.interval(confidence = 0.95, loc = trimmedMean, scale = st.sem(trimmed))
                    # confid_lvl = confidRange[0]
                    confid_lvl = abs(confidRange[1] - trimmedMean)
                    print(confidRange)
                    print("Confidence level is: ", confid_lvl)
                else:
                    confid_lvl = 0

                #Lower/Upper Bound: TrimmedMean - Confidence Interval
                if(column != 'BS_Eff'):
                    UorL_Bound = trimmedMean - confid_lvl
                elif(column == 'BS_Eff'): #this param uses upper bound
                    UorL_Bound = trimmedMean + confid_lvl
                print("Upper/Lower Bound is: ", UorL_Bound)

                #Reliability: Confidence Intver / Trimmed Mean * 100 
                if(trimmedMean != 0):
                    reliability = confid_lvl / trimmedMean * 100
                else:
                    reliability = 0
                print("Reliability is: ", reliability)

                #Parameter Value
                if(reliability < 10): 
                    parameter_value = trimmedMean
                else:
                    parameter_value = UorL_Bound

                print("Parameter Value is: ", parameter_value)
                print("\n")

                proportionCount = 0
                proportion = 0
                
                # insertData = [paramID, Quar1, Quar3, iqr, 0, max, straightMean, trimmedMean, stnd_deviation, totalCount, proportionCount, proportion, confid_lvl, UorL_Bound, reliability, parameter_value]
                # f = open('results2.csv','a',newline='')
                # write = csv.writer(f)
                # write.writerow(insertData)
                # f.close()

            elif(params.get(column, "NULL") == 'Prop'):
            #elif(column == 'OC_Stoves'):

                print(column)
                #print(data[column].describe())
                sql = "SELECT * FROM dbthezxpnokgxv.c_SurveyedParams WHERE ParameterName = '" + column + "';"
                result = conn.execute(sql).fetchall()
                for row in result:
                    #print(row)
                    paramID = row[0]

                #Mean
                straightMean = np.mean(selectData[column])
                print("The mean is: ", straightMean)

                #Total Count: total number of responses
                totalCount = selectData[column].count()
                print("Total count of proportion paramter is: ", totalCount)

                # Proportion Count: total number of responses = 'YES'
                proportionCount = (selectData[column] == 1).sum()   
                print("Total number of 'yes', ", proportionCount)

                #Proportion: Proportion Count / Total Count
                if(totalCount != 0):
                    proportion = proportionCount / totalCount
                else:
                    proportion = 0 
                print("Proportion is: ", proportion)

                # Confidence level
                confid_lvl = 1.96 * np.sqrt( (proportion *(1-proportion)) / totalCount)
                print("Confidence Interval is: ", confid_lvl)

                #Lower/Upper Bound
                UorL_Bound = straightMean - confid_lvl
                print("Upper/Lower Bound is ", UorL_Bound)

                #Reliability: Confidence Interval / Proportion * 100
                if(proportion != 0):
                    reliability = confid_lvl / proportion * 100
                else:
                    reliability = 0
                print("Reliability is: ", reliability)

                #Parameter Value 
                if(reliability < 10):
                    parameter_value = proportion 
                else:
                    parameter_value = UorL_Bound

                print("Parameter Value is: ", parameter_value)
                print("\n") 

                Quar1 = Quar3 = iqr = max = trimmedMean = stnd_deviation = 0

                # insertData = [paramID, Quar1, Quar3, iqr, 0, max, straightMean, trimmedMean, stnd_deviation, totalCount, proportionCount, proportion, confid_lvl, UorL_Bound, reliability, parameter_value]
                # f = open('results2.csv','a',newline='')
                # write = csv.writer(f)
                # write.writerow(insertData)
                # f.close()
            else:
                flag = 0

            # f = open('results2.csv','a',newline='')
            # write = csv.writer(f)
            # #write.writerow(header)
            # write.writerow(insertData)
            # f.close()

            if(flag != 0):
                values = {'paramID': paramID, 'mpvid': mpvid, 'groupID': groupID,
                    'Quar1': Quar1, 'Quar3': Quar3, 'iqr': iqr,'max': max, 
                    'straightMean': straightMean, 'trimmedMean': trimmedMean,'stnd_dev': stnd_deviation,
                    'totalCount': totalCount, 'propCount': proportionCount, 'proportion': proportion,
                    'confid_lvl': confid_lvl, 'ULbound': UorL_Bound, 'reliability': reliability, 'paramVal': parameter_value}

                sql= text('UPDATE dbthezxpnokgxv.c_MonitoringResults SET Quartile_1 = :Quar1, '
                    'Quartile_3 = :Quar3, IQR = :iqr, iqr_Min = 0, iqr_Max = :max, '
                    'Average = :straightMean, Average_iqr = :trimmedMean, SD = :stnd_dev, '
                    'TotalCount = :totalCount, Prop_Count = :propCount, Proportion = :proportion, '
                    'CI = :confid_lvl, UorL_Bound = :ULbound, Reliability = :reliability, Result = :paramVal '
                    'WHERE MRID > 14 and ParamID = :paramID and MPVID = :mpvid and GroupID = :groupID')  #ADDED MRID > 14 for TESTING PURPOSES
            
                conn.execute(sql, values)

    # monitoringResults = pd.read_csv('results2.csv') 
    # monitoringResults = monitoringResults.fillna(0)
    # print(monitoringResults)
    #monitoringResults.to_sql('c_MonitoringResults', conn, schema = 'dbthezxpnokgxv', if_exists = 'append', index = False)

    #os.remove(oldest_file) #DELETE file from folder