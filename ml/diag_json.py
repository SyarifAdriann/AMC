import json
data = json.load(open('c:/xampp/htdocs/AMC/ml/samples_v4_output.json', encoding='utf-8'))
s = data[0]
print('Sample 1 path:')
for node in s['tree0_path']:
    keys = list(node.keys())
    il = node['is_leaf']
    nid = node['node_id']
    print(f'  node_id={nid}  is_leaf={il}  type={type(il).__name__}  keys={keys}')
