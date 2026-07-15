#!/usr/bin/env python3
import pandas as pd, pickle, numpy as np, json, warnings
from pathlib import Path
warnings.filterwarnings('ignore')

ROOT = Path(__file__).resolve().parents[1]
with open(ROOT/'ml/encoders_redo.pkl','rb') as f: enc = pickle.load(f)
with open(ROOT/'ml/parking_stand_model_rf_redo.pkl','rb') as f: model = pickle.load(f)
valid_stands = set(enc['parking_stand'].classes_)
stand_classes = list(enc['parking_stand'].classes_)

CATMAP = {'Komersial':'COMMERCIAL','komersial':'COMMERCIAL','KOMERSIAL':'COMMERCIAL',
          'cargo':'CARGO','Cargo':'CARGO','Charter':'CHARTER','charter':'CHARTER'}
A0 = ['C 152','C 172','C 182','C 185','C 206','C 208','C 402','C 404','C 425',
      'PC 6','PC 12','CESSNA','PILATUS']

def nc(c): return CATMAP.get(str(c).strip(), str(c).strip().upper())
def sz(at):
    ac = str(at).strip().upper().replace(' ','')
    for x in A0:
        if x.replace(' ','') in ac or ac in x.replace(' ',''): return 'SMALL_A0_COMPATIBLE'
    return 'STANDARD'
def tr(ao):
    H=['BATIK AIR','CITILINK','GARUDA','TRIGANA','TRI MG']
    M=['PELITA','JETSET','KARISMA','JIP','PREMI','SUSI AIR']
    a=str(ao).strip().upper()
    return 'HIGH_FREQUENCY' if a in H else ('MEDIUM_FREQUENCY' if a in M else 'LOW_FREQUENCY')
def zn(cat): return 'RIGHT_COMMERCIAL' if cat=='COMMERCIAL' else ('LEFT_CARGO' if cat=='CARGO' else 'MIDDLE_CHARTER')
def se(e,v):
    cls=list(e.classes_); lk={c:i for i,c in enumerate(cls)}; return int(lk.get(v,0))

def predict(at, ao, cat):
    at2=str(at).strip().upper(); ao2=str(ao).strip().upper(); cat2=nc(cat)
    s=sz(at2); t=tr(ao2); z=zn(cat2)
    vec=[se(enc['aircraft_type'],at2), se(enc['aircraft_size'],s), se(enc['operator_airline'],ao2),
         se(enc['airline_tier'],t), se(enc['category'],cat2), se(enc['stand_zone'],z)]
    X=np.array(vec,dtype=np.int64).reshape(1,-1)
    proba=model.predict_proba(X)[0]
    top3=[]
    for rank,i in enumerate(np.argsort(proba)[::-1][:3]):
        top3.append({'rank':rank+1,'stand':stand_classes[int(model.classes_[i])],'prob':round(float(proba[i]),4)})
    return {'at':at2,'ao':ao2,'cat':cat2,'size':s,'tier':t,'zone':z,'vec':vec,'top3':top3}

df=pd.read_csv(ROOT/'DATASET_AMC_fields_used.csv')
df.columns=['aircraft_type','operator_airline','category','parking_stand']
df=df.dropna()
df['aircraft_type']=df['aircraft_type'].str.strip().str.upper()
df['operator_airline']=df['operator_airline'].str.strip().str.upper()
df['cat_norm']=df['category'].apply(nc)
df['actual_stand']=df['parking_stand'].str.strip().str.upper()
df=df[df['actual_stand'].isin(valid_stands)
       & df['aircraft_type'].isin(set(enc['aircraft_type'].classes_))
       & df['operator_airline'].isin(set(enc['operator_airline'].classes_))
       & df['cat_norm'].isin(['COMMERCIAL','CARGO','CHARTER'])].reset_index(drop=True)

manual = [
    ('ATR 72', 'PELITA',    'Komersial'),   # 1 - COMMERCIAL MEDIUM
    ('B 738',  'GARUDA',    'Komersial'),   # 2 - COMMERCIAL HIGH
    ('A 320',  'BATIK AIR', 'Komersial'),   # 3 - COMMERCIAL HIGH, diff airline
    ('ATR 72', 'CITILINK',  'Komersial'),   # 4 - COMMERCIAL HIGH, diff airline
    ('ATR 72', 'FLY JAYA',  'Komersial'),   # 5 - COMMERCIAL LOW
    ('G IV',   'JETSET',    'Charter'),     # 6 - CHARTER MEDIUM
    ('EMB 135','KARISMA',   'CHARTER'),     # 7 - CHARTER MEDIUM, diff
    ('BBJ',    'JIP',       'Charter'),     # 8 - CHARTER MEDIUM, diff
    ('B 733',  'TRI MG',    'cargo'),       # 9 - CARGO HIGH
    ('B 734',  'B. B. N.', 'cargo'),       # 10 - CARGO LOW
]

results = []
for i,(at,ao,cat) in enumerate(manual,1):
    cn = nc(cat)
    at2 = at.strip().upper(); ao2 = ao.strip().upper()
    match = df[(df['aircraft_type']==at2) & (df['operator_airline']==ao2) & (df['cat_norm']==cn)]
    actual = match['actual_stand'].iloc[0] if len(match) > 0 else '?'
    res = predict(at,ao,cat)
    in_top3 = any(t['stand']==actual for t in res['top3'])
    top3_str = ', '.join(str(t['stand'])+'('+str(round(t['prob']*100,1))+'%)' for t in res['top3'])
    check = 'BENAR' if in_top3 else 'SALAH'
    print(str(i).rjust(2)+'. '+at2.ljust(12)+ao2.ljust(16)+cn.ljust(13)+'actual='+actual.ljust(7)+'['+top3_str+']  ['+check+']')
    results.append({
        'no':i,'aircraft_type':at2,'operator_airline':ao2,
        'category_raw':cat,'category_norm':cn,'actual_stand':actual,
        'aircraft_size':res['size'],'airline_tier':res['tier'],'stand_zone':res['zone'],
        'encoded_vector':res['vec'],'top3':res['top3'],'in_top3':in_top3
    })

with open(ROOT/'ml/samples_10_output.json','w',encoding='utf-8') as f:
    json.dump(results,f,indent=2,ensure_ascii=False)
print('\nSaved to ml/samples_10_output.json')
