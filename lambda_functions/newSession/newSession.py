import sys
import logging
import rds_config
import pymysql
import json


db_host  = rds_config.db_endpoint
db_uname = rds_config.db_username
db_passwd = rds_config.db_password
db_name = rds_config.db_name
db_charset = 'utf8'
db_cursorclass = pymysql.cursors.DictCursor

def lambda_handler(event, context):
    
    gd_userid = event['userid']
    gd_sessionid = event['sessionid']
    gd_registrationcode = event['registrationcode']
    
    try:
        
        #  validate input
        if gd_userid == None or gd_userid == "":
            raise Exception('ERROR: UserID cannot be empty')
        if gd_sessionid == None or gd_sessionid == "":
            raise Exception('ERROR: SessionID cannot be empty')
        if gd_registrationcode == None or gd_registrationcode == "":
            raise Exception('ERROR: Registration Code cannot be empty')
        
        else:    
            # Execute query
            with conn.cursor() as cur:
                sql = "INSERT INTO usersessions (usersessionid, userid, expires, registrationcode) VALUES (%s, %s, DATE_ADD(NOW(), INTERVAL 7 DAY), %s)"
                result = cur.execute(sql, (gd_sessionid, gd_userid, gd_registrationcode,))
            conn.commit()
                
            #  validate results
            if result == 1:
                return json.dumps(result)
            else:
               raise Exception('ERROR: An unexpected error occurred getting the regs list.')
            
            
        
    except Exception as e:
        conn.rollback()
        raise Exception("ERROR: "+ str(e))
            

logger = logging.getLogger()
logger.setLevel(logging.INFO)

try:
    conn = pymysql.connect(db_host, user=db_uname, passwd=db_passwd, db=db_name, cursorclass=db_cursorclass, charset=db_charset, connect_timeout=5)
    logger.info("SUCCESS: Connection to RDS mysql instance succeeded")

except Exception as e:
    logger.error("ERROR: Unexpected error: Could not connect to MySql instance."+ str(e))
    sys.exit()
