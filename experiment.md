# Rekap Eksperimen — AMC Parking Stand Prediction
**Dataset:** DATASET_AMC_fields_used.csv  17 kelas parking stand  
**Fitur:** aircraft_type, aircraft_size, operator_airline, airline_tier, category, stand_zone (6 fitur)  
**Tanggal Eksperimen:** 2026-06-19

---

## Daftar Eksperimen

| # | Eksperimen | Metode | Split | Resampling |
|---|-----------|--------|-------|------------|
| 1 | **Baseline Thesis (Resmi)** | Random Forest (Penelitian Ini) | 80/20 | SMOTE |
| 2 | **J48 Baseline** | DecisionTree (criterion=entropy) | 80/20 | SMOTE |
| 3 | **AlBassam (2025) — RandomForest / RandomOverSampler** | RandomForestClassifier | 80/20 | RandomOverSampler |
| 4 | **AlBassam (2025) — DecisionTree / RandomOverSampler** | DecisionTreeClassifier | 80/20 | RandomOverSampler |
| 5 | **AlBassam (2025) — SVC / RandomOverSampler** | SVC (RBF) | 80/20 | RandomOverSampler |
| 6 | **AlBassam (2025) — LogReg / RandomOverSampler** | LogisticRegression | 80/20 | RandomOverSampler |
| 7 | **AlBassam (2025) — KNN / RandomOverSampler** | KNeighborsClassifier | 80/20 | RandomOverSampler |
| 8 | **AlBassam (2025) — GNB / RandomOverSampler** | GaussianNB | 80/20 | RandomOverSampler |
| 9 | **AlBassam (2025) — RandomForest / SMOTE** ⭐ | RandomForestClassifier | 80/20 | SMOTE |
| 10 | **AlBassam (2025) — DecisionTree / SMOTE** | DecisionTreeClassifier | 80/20 | SMOTE |
| 11 | **AlBassam (2025) — SVC / SMOTE** | SVC (RBF) | 80/20 | SMOTE |
| 12 | **AlBassam (2025) — LogReg / SMOTE** | LogisticRegression | 80/20 | SMOTE |
| 13 | **AlBassam (2025) — KNN / SMOTE** | KNeighborsClassifier | 80/20 | SMOTE |
| 14 | **AlBassam (2025) — GNB / SMOTE** | GaussianNB | 80/20 | SMOTE |
| 15 | **Sahadevan (2023) — MLP** | MLPClassifier (10,10,10) | 75/25 | SMOTE |

> **Catatan:** ADASYN diuji namun gagal — tidak kompatibel dengan distribusi kelas dataset AMC
> (kelas minoritas terlalu sedikit sampel). ADASYN tidak dimasukkan dalam tabel hasil.

---

## Tabel Hasil Lengkap

| # | Model | Resampler | Top-1 Acc | Top-3 Acc | Top-5 Acc | Macro Prec | Macro Rec | Macro F1 | MCC | ROC-AUC |
|---|-------|-----------|:---------:|:---------:|:---------:|:----------:|:---------:|:--------:|:---:|:-------:|
| 1 | **RF Thesis (Resmi)** | SMOTE (original) | **36.22%** | **80.34%** | **98.94%** | **35.64%** | **38.74%** | **33.51%** | **0.3298** | **0.9240** |
| 2 | J48 / DecisionTree (entropy) | SMOTE | 38.15% | 79.67% | 96.63% | 39.75% | 40.25% | 35.35% | — | — |
| 3 | RF / RandomOverSampler | RandomOverSampler | 27.94% | 58.38% | 78.52% | 30.29% | 30.71% | 26.42% | 0.2483 | 0.8221 |
| 4 | DecisionTree / RandomOverSampler | RandomOverSampler | 27.65% | 57.61% | 77.65% | 32.82% | 30.58% | 26.83% | 0.2449 | 0.8105 |
| 5 | SVC / RandomOverSampler | RandomOverSampler | 26.97% | 57.42% | 78.81% | 29.50% | 29.17% | 23.89% | 0.2384 | 0.8114 |
| 6 | LogReg / RandomOverSampler | RandomOverSampler | 26.78% | 55.97% | 75.14% | 17.17% | 25.57% | 18.63% | 0.2162 | 0.7738 |
| 7 | KNN / RandomOverSampler | RandomOverSampler | 25.63% | 56.17% | 70.81% | 24.41% | 26.97% | 21.61% | 0.2175 | 0.7328 |
| 8 | GaussianNB / RandomOverSampler | RandomOverSampler | 11.27% | 24.37% | 46.24% | 7.82% | 18.46% | 10.07% | 0.0915 | 0.7575 |
| 9 | **RF / SMOTE** ⭐ *AlBassam Best* | SMOTE | 28.03% | 58.57% | 78.61% | 30.18% | 30.92% | 27.38% | 0.2490 | 0.8186 |
| 10 | DecisionTree / SMOTE | SMOTE | 27.94% | 58.19% | 78.13% | 31.78% | 30.89% | 27.41% | 0.2478 | 0.8109 |
| 11 | SVC / SMOTE | SMOTE | 26.20% | 57.32% | 78.52% | 27.55% | 28.63% | 23.42% | 0.2302 | 0.8124 |
| 12 | LogReg / SMOTE | SMOTE | 26.11% | 55.01% | 75.43% | 15.24% | 24.88% | 17.46% | 0.2088 | 0.7704 |
| 13 | KNN / SMOTE | SMOTE | 23.60% | 49.42% | 60.79% | 22.45% | 26.52% | 21.33% | 0.1973 | 0.7040 |
| 14 | GaussianNB / SMOTE | SMOTE | 11.27% | 24.66% | 44.99% | 7.83% | 18.46% | 10.12% | 0.0913 | 0.7561 |
| 15 | **MLP (10,10,10)** *Sahadevan* | SMOTE | 23.04% | 57.16% | 72.65% | 10.67% | 21.92% | 12.66% | 0.1842 | 0.7534 |
| 16 | **Naive Bayes (GaussianNB)** *Thesis Pipeline* | SMOTE | 28.52% | 68.69% | 98.84% | 25.65% | 31.09% | 21.43% | 0.2482 | 0.9011 |

> **Bold** = nilai terbaik per metrik di antara semua metode pembanding.  
> ⭐ = kombinasi terbaik dalam kerangka AlBassam (2025).  
> Baris 1: MCC & ROC-AUC dihitung dengan pipeline asli (`parking_stand_model_rf_redo.pkl`, `encoders_redo.pkl`, tanpa MinMaxScaler).  
> Baris 2: MCC & ROC-AUC tidak dihitung (pipeline J48 tidak menghasilkan probability matrix yang sesuai).

---

## Ringkasan Perbandingan (3 Metode Utama)

| Method | Sumber | Split | Resampler | Top-1 | Top-3 | Top-5 | Macro F1 | MCC | ROC-AUC |
|--------|--------|-------|-----------|:-----:|:-----:|:-----:|:--------:|:---:|:-------:|
| **RF Penelitian Ini** ⭐ | Penelitian Ini | 80/20 | SMOTE (orig) | **36.22%** | **80.34%** | **98.94%** | **33.51%** | **0.3298** | **0.9240** |
| RF (AlBassam best) | AlBassam & AlShahrani (2025) | 80/20 | SMOTE | 28.03% | 58.57% | 78.61% | 27.38% | 0.2490 | 0.8186 |
| MLP (10,10,10) | Sahadevan et al. (2023) | 75/25 | SMOTE | 23.04% | 57.16% | 72.65% | 12.66% | 0.1842 | 0.7534 |

---

## Catatan Penting

### Mengapa Top-3 RF Thesis Jauh Lebih Tinggi?

RF Penelitian Ini (baris 1) menggunakan **pipeline produksi asli** yang berbeda dari eksperimen AlBassam/Sahadevan:

| Aspek | RF Thesis (Resmi) | AlBassam / Sahadevan Comparison |
|-------|:-----------------:|:-------------------------------:|
| Hyperparameter | GridSearchCV khusus (`class_weight=balanced_subsample`) | GridSearchCV standar (tanpa `class_weight`) |
| Scaler | Tidak ada (raw LabelEncoder) | MinMaxScaler |
| SMOTE | SMOTE asli dari pipeline | SMOTE pada split baru |
| Split seed | Sama (random_state=42) | Sama (random_state=42) |

`class_weight='balanced_subsample'` pada RF thesis adalah kunci — ia menangani ketidakseimbangan kelas secara internal per-pohon, yang sangat efektif untuk dataset AMC yang memiliki distribusi kelas tidak merata (A0: 90 rekaman vs B1: 565 rekaman).

### Catatan ADASYN
ADASYN gagal dijalankan karena beberapa kelas parking stand di dataset AMC memiliki jumlah sampel yang terlalu sedikit (< k_neighbors) untuk ADASYN menghasilkan sampel sintetis.

---

## Konfigurasi Detail

### Eksperimen 1 — RF Penelitian Ini (Resmi)
```
Model       : RandomForestClassifier
n_estimators: 200
max_depth   : None
min_samples_leaf: 5
min_samples_split: 2
class_weight: balanced_subsample
Resampling  : SMOTE(random_state=42)
Split       : 80/20 stratified, random_state=42
Data source : parking_history_encoded_redo.csv (5190 rows)
```

### Eksperimen 2 — J48 Baseline
```
Model       : DecisionTreeClassifier(criterion='entropy')
Resampling  : SMOTE(random_state=42)
Split       : 80/20 stratified, random_state=42
Data source : parking_history_encoded_redo.csv (5190 rows)
```

### Eksperimen 3–14 — AlBassam & AlShahrani (2025)
```
Preprocessing: LabelEncoder + MinMaxScaler
Resampler    : RandomOverSampler / SMOTE / ADASYN (ADASYN gagal)
GridSearchCV : 10-fold StratifiedKFold, scoring='accuracy'
Split        : 80/20 stratified, random_state=42
RF params    : n_estimators=[50,100,200], max_depth=[None,10,20], min_samples_split=[2,5]
DT params    : max_depth=[None,5,10,20], min_samples_split=[2,5,10]
SVC params   : C=[0.1,1,10], kernel=['rbf','linear']
LR params    : C=[0.01,0.1,1,10], max_iter=1000
KNN params   : n_neighbors=[3,5,7,11]
GNB params   : var_smoothing=[1e-9,1e-8,1e-7]
```

### Eksperimen 15 — Sahadevan et al. (2023)
```
Model       : MLPClassifier(hidden_layer_sizes=(10,10,10), activation='relu',
              solver='adam', max_iter=500, random_state=2,
              early_stopping=True, validation_fraction=0.1)
Resampling  : SMOTE (best resampler dari Eksperimen AlBassam)
Split       : 75/25 stratified, random_state=42
```

---

## File Output

| File | Lokasi |
|------|--------|
| Script utama | `ml/comparison_experiments.py` |
| Hasil AlBassam (detail) | `ml/reports/results_albassam.md` |
| Hasil Sahadevan (detail) | `ml/reports/results_sahadevan.md` |
| Ringkasan perbandingan | `ml/reports/comparison_summary.md` |
| J48 Baseline | `ml/j48_baseline_results.txt` |
| Confusion Matrix RF | `ml/reports/confusion_matrix_RF.png` |
| Confusion Matrix AlBassam | `ml/reports/confusion_matrix_albassam_best.png` |
| Confusion Matrix MLP | `ml/reports/confusion_matrix_mlp.png` |
| Class Distribution | `ml/reports/class_distribution.png` |

---

## MCC & ROC-AUC — RF Penelitian Ini (Pipeline Asli)

Dihitung dengan pipeline produksi asli (data: `parking_history_encoded_redo.csv`,
model: `parking_stand_model_rf_redo.pkl`, split 80/20 stratified random_state=42, SMOTE).
Model **dimuat langsung** — tidak dilatih ulang.

| Metrik | Nilai |
|--------|-------|
| **MCC** | **0.3298** |
| **ROC-AUC (macro OvR)** | **0.9240** |

*Test set: 1038 sampel (17 kelas terwakili)*
