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
    
    gd_context = event['context']
    gd_message = event['message']
    gd_ipaddress = event['ipaddress']
    gd_userid = event['userid']
    
    try:
        
        # Execute query
        with conn.cursor() as cur:
            sql = "INSERT INTO auditlog (context, message, logdate, ipaddress, userid) VALUES (%s, %s, NOW(), %s, %s)"
            cursor = cur.execute(sql, (gd_context, gd_message, gd_ipaddress, gd_userid,))
        conn.commit()

        #  validate results
        if cursor == 1:
            return
        else:
           raise Exception('ERROR: Unknown error occured. Audit Log Failed')
        
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
