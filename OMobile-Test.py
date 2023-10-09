import time
start = time.time()

import MySQLdb
import urllib
import re
from subprocess import call


#fixes words, capitalizing each word in a string. equivalent to ucwords() in php
def nameFix(aString):
	newString = aString
	newString = newString.lower()
	if (' ' in newString):
		splits = newString.split()	
		for i in range(0, len(splits)):
			splits[i] = splits[i].capitalize()
		newString = (" ").join(splits)
		#print newString
	else: newString = newString.capitalize()
	return newString

#makes the queried address elements for comparing with the sites pulled address
def addressMaker(theVars):
	theAddress = theVars[2].capitalize()
	theArea = theVars[7]
	if (theVars[1] != None): theAddress = str(theVars[1]) + '. ' + theAddress
	if (theVars[0] != None): theAddress = str(theVars[0]) + ' ' + theAddress
	if (theVars[3] != None): theAddress += ' ' + str(theVars[3])
	if (theVars[4] != None): theAddress += ' ' + str(theVars[4])
	if (theVars[6] != None): theArea = theVars[6] + ' ' + theArea
	if (theVars[5] != None): theArea = nameFix(theVars[5]) + ", " + theArea
	if (theVars[8] != None): theArea += "-" + str(theVars[8])
	theAddress= nameFix(theAddress)
	return [theAddress,theArea]

#remove extra spaces for acurate testing comparisons that wont fail over whitespace
def noDubs(aString):
	newString=''
	for word in aString.split():
	    newString=newString+' '+word
	return newString.strip()


#Primary testing function
def runTests(runTimes):
	db = MySQLdb.connect(host="127.0.0.1", port=5455, user="jacob.wellinghof",passwd="nyx!0=day,T",db="cec_report")
	c=db.cursor()
	ranCount=0
	runSpeeds=[]
	for i in range(0,runTimes):
		newRun = time.time()
		print("Run: " + str(i+1))
		#fetching urls html  variables for testing
		f = urllib.urlopen("http://localhost/archived-index.php?testMode=thequickbrownfoxomg98838459758575984794")
		s = f.readlines()
		f.close()
		foundTable = 0
		gotMeta=0
		lastTest=0
		idNums=[]
		table=[]
		theSite=None
		for line in s:
			if gotMeta < 2:
				if (line[0:5] == "<meta"):
					m = re.search("'\d*'",line)
					idNums.append(int(m.group(0).strip("'")))
					gotMeta+=1
			elif (gotMeta ==2):
				if (not foundTable):
					if (line[0:17] == "<table id='info'>"):
						foundTable=1
				else:
					if (line[0:4] =="<tr>"):
						blah = re.split('[><]',line)
						blahs = blah[5].split("id='")[1].strip("'")
						#if case added for address row which has a <br> 
							#which adds an extra crucial element to the regex split
						if (blah[13] != None and blah[14] != '') :
							table.append([blahs, blah[8],noDubs(blah[12]),noDubs(blah[14])])	
						else:
							table.append([blahs, blah[8],noDubs(blah[12])])		
		try:
			[cID,uID] = idNums	
		except:
			print("could not get meta data fffffuuuuu\n\n\n")
			print s
			break
		#db querying and testing begins
		c.execute("""SELECT customer.site_id,utility_acct.id, customer_contact.first_name, customer_contact.last_name,customer_contact.name_suffix, customer_contact.phone_1,customer_contact.email FROM utility_acct,customer,customer_contact WHERE utility_acct.customer_id = %s AND customer.id=utility_acct.customer_id AND customer.id=customer_contact.customer_id;""",(cID))
		theResults = c.fetchone()
		[qSID, qUID, qFName, qLName, qSuffix, qPhone,qEmail] = theResults
		spot = 0
		#test 1: checking UID matches tables
		lastTest = 1
		if (qUID!=uID):
			break
		#now that uid is verified lets pull the site and parcel info
		#coming soon
		else:
			c.execute("""SELECT site.house_number, site.street_predirection, site.street_name, site.street_postdirection, site.unit, site.city, site.state_code, site.zip_code, site.zip_add_on, site_parcel.living_sqft, site_parcel.ac_type, site_parcel.heat_type, site_parcel.dwelling_type, site_parcel.pool, site_parcel.spa FROM site,site_parcel WHERE site.id = %s AND site_parcel.site_id=site.id;""",(qSID))
			theSite = c.fetchone()
				
		#test 2 checking if name matches tables and was formated properly
		lastTest = 2	
		if (qFName != None or qLName !=None):
			if (qFName != None and qLName !=None):
				qName = nameFix(qFName) + " " + nameFix(qLName)
			elif (qLName !=None):
				qName = nameFix(qLName)
			else: qName = nameFix(qFName)
			if (qSuffix != None) : qName += " " + qSuffix.capitalize()
			if (table[spot] != ['name', 'Name', qName]): break
			spot+=1
		
		#test 3 checking phone
		lastTest = 3
		if (qPhone != None): 
			if (table[spot] != ['phone', 'Phone', qPhone]): break			
			spot+=1				
		
		#test 4 checking email
		lastTest = 4
		if (qEmail != None):
			if (table[spot] != ['email', 'Email', qEmail]): break
			spot+=1
			
		#test 5 address (time ugh should never need a if null test ish)
		lastTest = 5
		qAddress = addressMaker(theSite[0:-6])
		if (table[spot] != ['address', "Address", qAddress[0], qAddress[1]]):
			print qAddress
			break
		spot+=1
		
		#test 6 dwelling_type
		lastTest = 6
		if (theSite[12] != None):
			tid = table[spot][0]
			if ("FAMILY" in tid ): 
				table[spot][0] = tid[:4]
				table[spot][2] = table[spot][2][:4].upper()
			else:
				print("Isn't this supposed to never?")
				break
			if (table[spot] != [theSite[12],'Space', theSite[12]]):break
			spot+=1	
		
		
		#test 7 sqft
		lastTest = 7
		if (theSite[9] != None):
			table[spot][2] = int(table[spot][2].split()[0])
			if (table[spot] != ["size", "Size", theSite[9]]): break
			spot+=1
		
		#test 8 heat_type
		lastTest = 8
		if (theSite[11] != None):
			table[spot][2] = table[spot][2].split()[0].upper()
			if (table[spot][2] == "ELECTRIC"): table[spot][2] ="ELEC"
			if (table[spot] != [theSite[11], "Heat", theSite[11]]): break
			spot+=1
		
		#test 9 ac_type
		lastTest = 9
		if (theSite[10] != None):
			table[spot][2] = table[spot][2].split()[0].upper()
			if (table[spot] != [theSite[10], "AC", theSite[10]]): break
			spot+=1
			
		#test 10 pool
		lastTest = 10
		if (theSite[13] != None):
			table[spot][2] = table[spot][2].split()[3].upper()[0:-1]
			if (table[spot] != [theSite[13], "Pool", theSite[13]]): break
			spot+=1
		
		#test 11 space
		lastTest = 10
		if (theSite[13] != None):
			table[spot][2] = table[spot][2].split()[3].upper()[0:-1]
			if (table[spot] != [theSite[14], "Spa", theSite[14]]): break
			spot+=1
					
		#Tests completed
		ranCount+=1
		aRun = time.time()
		runSpeeds.append(aRun-newRun)
		
	#error printing on breaks
	if ranCount !=runTimes:
		#UI warning an error occured
		call("/Scripts/dialogscript.app/Contents/MacOS/applet")
		print("Failed on test: " + str(lastTest) + "\n\n")
		print("Variables:")
		print table[spot]
		print idNums
		print table
		print theResults
		print theSite
	else: print("PERFECTION!")	
	
	return runSpeeds
		
		
loopTimes = 9001	
runSpeeds = runTests(loopTimes)
end = time.time()
elapsed= end - start
mins = elapsed/60
print "Running ", loopTimes, " times took a total of ", elapsed, "seconds to run, aka ", mins, "minutes"
avgTime = sum(runSpeeds)/len(runSpeeds)
print "Test took an average of ", avgTime, "seconds."