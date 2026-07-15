from pathlib import Path
md = Path('c:/xampp/htdocs/AMC/CLI/revision/revisibab4_v3_4-2-4only.md').read_text(encoding='utf-8')
checks = [
    ('Teori pembuka formula Gini', 'Gini(t) = 1 - Σ [p(i|t)]²'),
    ('P_vote formula', 'P_vote(stand_j)'),
    ('Komponen A', 'Komponen A — Input Mentah'),
    ('Komponen C Gini root', 'Komponen C — Gini Impurity di Root Node'),
    ('Root distribution', 'Distribusi kelas di root'),
    ('Gini root calc', 'Gini(root) = 1 - Σ p(i|root)²'),
    ('Komponen D tree path', 'Komponen D — Penelusuran Pohon Keputusan'),
    ('LEAF row in table', '(LEAF)'),
    ('Gini leaf calc', 'Gini(leaf) = 1 -'),
    ('Komponen E voting', 'Komponen E — Voting 200 Pohon'),
    ('Vote total row', 'Total'),
    ('Komponen F', 'Komponen F — Hasil predict_proba'),
    ('predict_proba note', 'rata-rata probabilitas daun'),
    ('Top-3 recommendation', 'Top-3 Rekomendasi Sistem'),
    ('BENAR verdict', 'BENAR'),
    ('SALAH verdict', 'SALAH'),
    ('7/10 summary', '7/10'),
    ('4.2.5 section', '4.2.5 Analisis Pengaruh'),
    ('Thesis 80.15%', '80.15%'),
    ('Sampel 10 header', 'Sampel 10'),
]
print('SPOT CHECK:')
for label, needle in checks:
    found = needle in md
    status = 'OK' if found else 'FAIL'
    print(f'  [{status}] {label}')
print()
n_a = md.count('Komponen A — Input Mentah')
n_c = md.count('Komponen C — Gini Impurity di Root Node')
n_e = md.count('Komponen E — Voting 200 Pohon')
print(f'  Komponen A count: {n_a} (expected 10)')
print(f'  Komponen C count: {n_c} (expected 10)')
print(f'  Komponen E count: {n_e} (expected 10)')
print()
print(f'Total lines: {len(md.splitlines())}')
print(f'Total bytes: {len(md.encode("utf-8"))}')
