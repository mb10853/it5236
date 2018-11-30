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
    
    gd_passwordresetid = event['passwordresetid']
    
    try:
        
        #  validate input
        if gd_passwordresetid == None or gd_passwordresetid == "":
            raise Exception('ERROR: Password Reset ID cant be empty')
        else:
            # Execute query
            with conn.cursor() as cur:
                sql = "SELECT userid FROM passwordreset WHERE passwordresetid = %s AND expires > NOW()"
                cursor = cur.execute(sql, (gd_passwordresetid,))
                result = cur.fetchone()
            conn.commit()
            
           
            if cursor < 1:
                raise Exception('ERROR: Unknown error occured')
            else:
                results = {'rowcount':cursor,'userid':result['userid']}
                return json.dumps(results)
        
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