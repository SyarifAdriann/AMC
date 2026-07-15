#!/usr/bin/env python3
"""
ml/extract_db_data.py
Extract training data from the AMC MySQL database.
"""
import sys, json, pickle, warnings
from pathlib import Path

import pymysql
import pandas as pd
import numpy as np

warnings.filterwarnings('ignore')

ROOT = Path(__file__).resolve().parents[1]

DB_CFG = dict(host='localhost', port=3306, db='amc', user='root', password='',
              charset='utf8mb4', cursorclass=pymysql.cursors.DictCursor)

def connect():
    try:
        conn = pymysql.connect(**DB_CFG)
        print('[DB] Connected to MySQL amc database')
        return conn
    except Exception as e:
        print(f'[DB] Connection failed: {e}')
        sys.exit(1)

conn = connect()
cur  = conn.cursor()

# 1. List all tables
cur.execute('SHOW TABLES')
tables = [list(r.values())[0] for r in cur.fetchall()]
print(f'[DB] Tables: {tables}')

# 2. For each table show row count + columns
for t in tables:
    cur.execute(f'SELECT COUNT(*) as n FROM `{t}`')
    n = cur.fetchone()['n']
    cur.execute(f'DESCRIBE `{t}`')
    cols = [r['Field'] for r in cur.fetchall()]
    print(f'  {t}: {n} rows, cols={cols}')

conn.close()
