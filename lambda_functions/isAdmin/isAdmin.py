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
    
    try:
        
        #  validate input
        if gd_userid == None or gd_userid == "":
            raise Exception('ERROR: UserID cannot be empty')
        
        else:
            # Read results
            with conn.cursor() as cur:
                sql = "SELECT isadmin FROM users WHERE userid = %s"
                cur.execute(sql, (gd_userid,))
                result = cur.fetchone()
            conn.commit()
                
            
            #  validate results
            if result == None or result == "":
                raise Exception('ERROR: An unexpected error occurred getting the result.')
            else:
                if result['isadmin'] == 0:
                    return False
                elif result['isadmin'] == 1:
                    return True
                else:
                    raise Exception('ERROR: An unexpected error occurred getting the result.')
            
            
        
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
