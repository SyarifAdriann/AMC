# Hasil Eksperimen AlBassam & AlShahrani (2025)

**Referensi:** AlBassam, B. H., & AlShahrani, A. M. (2025). *Flight delay prediction:
Evaluating machine learning algorithms for enhanced accuracy.* PLOS ONE.

**Dataset:** DATASET_AMC_fields_used.csv (5190 baris valid, 17 kelas parking stand)
**Split:** 80% train / 20% test, random_state=42
**Validasi:** 10-fold StratifiedKFold CrossValidation
**Preprocessing:** LabelEncoder + MinMaxScaler
**Resampling:** Diterapkan pada training set saja
**Metrik:** Top-1/3/5 Accuracy, Macro Precision/Recall/F1, Weighted F1, MCC, ROC-AUC

> **Catatan ADASYN:** ADASYN tidak kompatibel dengan distribusi kelas dataset AMC
> (beberapa kelas memiliki terlalu sedikit sampel). Baris ADASYN ditandai *GAGAL*.

---

## Tabel Hasil (6 Classifier x 3 Resampler = 18 Baris)

| Classifier | Resampler | Top-1 Acc | Top-3 Acc | Top-5 Acc | Macro Prec | Macro Rec | Macro F1 | Weighted F1 | MCC | ROC-AUC | Best Params |
|------------|-----------|-----------|-----------|-----------|------------|-----------|----------|-------------|-----|---------|-------------|
| RandomForest | RandomOverSampler | 27.94% | 58.38% | 78.52% | 30.29% | 30.71% | 26.42% | 26.16% | 0.2483 | 0.8221 | `{'max_depth': 10, 'min_samples_split': 5, 'n_estimators': 100}` |
| DecisionTree | RandomOverSampler | 27.65% | 57.61% | 77.65% | 32.82% | 30.58% | 26.83% | 26.38% | 0.2449 | 0.8105 | `{'max_depth': None, 'min_samples_split': 2}` |
| SVC | RandomOverSampler | 26.97% | 57.42% | 78.81% | 29.50% | 29.17% | 23.89% | 23.60% | 0.2384 | 0.8114 | `{'C': 10, 'kernel': 'rbf'}` |
| LogisticRegression | RandomOverSampler | 26.78% | 55.97% | 75.14% | 17.17% | 25.57% | 18.63% | 20.12% | 0.2162 | 0.7738 | `{'C': 10}` |
| KNeighbors | RandomOverSampler | 25.63% | 56.17% | 70.81% | 24.41% | 26.97% | 21.61% | 21.53% | 0.2175 | 0.7328 | `{'n_neighbors': 5}` |
| GaussianNB | RandomOverSampler | 11.27% | 24.37% | 46.24% | 7.82% | 18.46% | 10.07% | 5.58% | 0.0915 | 0.7575 | `{'var_smoothing': 1e-09}` |
| RandomForest * | SMOTE | 28.03% | 58.57% | 78.61% | 30.18% | 30.92% | 27.38% | 27.05% | 0.2490 | 0.8186 | `{'max_depth': 20, 'min_samples_split': 2, 'n_estimators': 100}` |
| DecisionTree | SMOTE | 27.94% | 58.19% | 78.13% | 31.78% | 30.89% | 27.41% | 27.06% | 0.2478 | 0.8109 | `{'max_depth': None, 'min_samples_split': 2}` |
| SVC | SMOTE | 26.20% | 57.32% | 78.52% | 27.55% | 28.63% | 23.42% | 23.03% | 0.2302 | 0.8124 | `{'C': 10, 'kernel': 'rbf'}` |
| LogisticRegression | SMOTE | 26.11% | 55.01% | 75.43% | 15.24% | 24.88% | 17.46% | 19.09% | 0.2088 | 0.7704 | `{'C': 10}` |
| KNeighbors | SMOTE | 23.60% | 49.42% | 60.79% | 22.45% | 26.52% | 21.33% | 19.35% | 0.1973 | 0.7040 | `{'n_neighbors': 5}` |
| GaussianNB | SMOTE | 11.27% | 24.66% | 44.99% | 7.83% | 18.46% | 10.12% | 5.63% | 0.0913 | 0.7561 | `{'var_smoothing': 1e-09}` |
| RandomForest | ADASYN | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | `ADASYN ERROR: No samples will be generated with the provided ratio settings.` |
| DecisionTree | ADASYN | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | `ADASYN ERROR: No samples will be generated with the provided ratio settings.` |
| SVC | ADASYN | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | `ADASYN ERROR: No samples will be generated with the provided ratio settings.` |
| LogisticRegression | ADASYN | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | `ADASYN ERROR: No samples will be generated with the provided ratio settings.` |
| KNeighbors | ADASYN | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | `ADASYN ERROR: No samples will be generated with the provided ratio settings.` |
| GaussianNB | ADASYN | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | N/A | `ADASYN ERROR: No samples will be generated with the provided ratio settings.` |

\* = kombinasi terbaik berdasarkan Top-1 Accuracy

---

## Kombinasi Terbaik

- **Classifier:** RandomForest
- **Resampler:** SMOTE
- **Top-1 Accuracy:** 28.03%
- **Top-3 Accuracy:** 58.57%
- **Top-5 Accuracy:** 78.61%
- **Macro Precision:** 30.18%
- **Macro Recall:** 30.92%
- **Macro F1:** 27.38%
- **Weighted F1:** 27.05%
- **MCC:** 0.2490
- **ROC-AUC:** 0.8186
- **Best Params:** `{'max_depth': 20, 'min_samples_split': 2, 'n_estimators': 100}`

---

## Confusion Matrix

![Confusion Matrix — AlBassam Best](confusion_matrix_albassam_best.png)
