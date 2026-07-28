from pathlib import Path
md = Path('c:/xampp/htdocs/AMC/CLI/revision/revisibab4_v4_fixed.md').read_text(encoding='utf-8')

fails = []
checks = [
    ('No n_samples=0', 'n_samples = 0' not in md),
    ('Root n_samples 2610', '2,610' in md or '2610' in md),
    ('Root Gini 0.9412', '0.9412' in md),
    ('Leaf Gini 0.6732', '0.6732' in md),
    ('Real stand names in root', 'Stand B1' in md or 'Stand A3' in md),
    ('Root path correct', '| **0** | aircraft_size' in md),
    ('Node 2 in path', '| **2** | category' in md),
    ('Leaf node 143', '| **143** (LEAF)' in md),
    ('10x Komponen C', md.count('Komponen C — Gini Impurity Root Node') == 10),
    ('10x Komponen D', md.count('Komponen D — Jalur Pohon') == 10),
    ('10x Komponen E', md.count('Komponen E — Voting 200 Pohon') == 10),
    ('10x Komponen F', md.count('Komponen F — predict_proba') == 10),
    ('4.2.5 section', '4.2.5 Analisis Pengaruh' in md),
    ('80.15% present', '80.15%' in md),
]
for label, ok in checks:
    status = 'OK' if ok else 'FAIL'
    print(f'  [{status}] {label}')
    if not ok:
        fails.append(label)

print()
print(f'Lines: {len(md.splitlines())}  Bytes: {len(md.encode("utf-8"))}')

# Show Sample 1 root node section
idx = md.find('Root Node (Node 0)')
print()
print('--- SAMPLE 1 ROOT NODE EXCERPT ---')
print(md[idx:idx+500])
