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
    gd_passwordHash = event['passwordHash']
    
    try:
        
        #  validate input
        if gd_userid == None or gd_userid == "":
            raise Exception('ERROR: UserID cannot be empty')
        if gd_passwordHash == None or gd_passwordHash == "":
            raise Exception('ERROR: Password cannot be empty')
        else:    
            # Execute query
            with conn.cursor() as cur:
                sql = "UPDATE users SET passwordHash=%s WHERE userid=%s"
                cur.execute(sql, (gd_userid, gd_passwordHash,))
                conn.commit()
            
            # Read results
            with conn.cursor() as cur:
                sql = "SELECT passwordHash FROM users WHERE userid=%s"
                cur.execute(sql, (gd_userid,))
                result = cur.fetchone()
                print(result)
            
            #  validate results
            if result == None:
                raise Exception('ERROR: Invalid UserID')
            elif result['passwordHash'] == gd_passwordHash:
                return{
                    'message':'SUCCESS: Password updated.'
                }
            else:
               raise Exception('ERROR: Unknown error occured. Password was not updated')

        
    except pymysql.err.InternalError as e:
        code, msg = e.args
        logger.error(error_codes.get(code, msg))
        conn.rollback()
            

logger = logging.getLogger()
logger.setLevel(logging.INFO)

try:
    conn = pymysql.connect(db_host, user=db_uname, passwd=db_passwd, db=db_name, cursorclass=db_cursorclass, charset=db_charset, connect_timeout=5)
    logger.info("SUCCESS: Connection to RDS mysql instance succeeded")

except Exception as e:
    logger.error("ERROR: Unexpected error: Could not connect to MySql instance."+ str(e))
    sys.exit()
