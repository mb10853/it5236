import sys
import logging
import rds_config
import pymysql
import json
import datetime


db_host  = rds_config.db_endpoint
db_uname = rds_config.db_username
db_passwd = rds_config.db_password
db_name = rds_config.db_name
db_charset = 'utf8'
db_cursorclass = pymysql.cursors.DictCursor


def datetime_handler(x):
    if isinstance(x, datetime.datetime):
        return x.isoformat()
    raise Exception("Unknown type")

def lambda_handler(event, context):
    
    gd_userid = event['userid']
    gd_registrationcode = event['registrationcode']
    
    try:
        
        #  validate input
        if gd_userid == None or gd_userid == "":
            raise Exception('ERROR: Userid cant be empty')
        else:
            # Execute query
            with conn.cursor() as cur:
                sql = "SELECT gameid, thingname, thingcover, thingsummary, convert_tz(things.thingcreated,@@session.time_zone,'America/New_York') as thingcreated FROM things LEFT JOIN users ON things.thinguserid = users.userid WHERE thingregistrationcode = %s AND userid= %s ORDER BY things.thingcreated ASC"
                cursor = cur.execute(sql, (gd_registrationcode, gd_userid,))
                result = cur.fetchall()
            conn.commit()
            
            if cursor < 1:
                 raise Exception('ERROR: User not found')
            elif result == None or result == "":
                raise Exception('ERROR: Unknown error occured')
            else:
                return json.dumps(result, default=datetime_handler)
            
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