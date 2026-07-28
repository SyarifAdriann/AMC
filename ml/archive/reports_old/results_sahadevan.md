# Hasil Eksperimen Sahadevan et al. (2023) — MLP Framework

**Referensi:** Sahadevan, A. S., et al. (2023). *Optimising Airport Ground Resource
Allocation for Multiple Aircraft Using Machine Learning-Based Arrival Time Prediction.*
MDPI Aerospace, 10(10), 879.

**Dataset:** DATASET_AMC_fields_used.csv (5190 baris valid, 17 kelas parking stand)
**Split:** 75% train / 25% test, random_state=42
**Validasi:** 10-fold StratifiedKFold CrossValidation
**Preprocessing:** LabelEncoder + MinMaxScaler
**Resampling:** SMOTE (resampler terbaik dari Eksperimen 1)
**Arsitektur MLP:** hidden_layer_sizes=(10,10,10), activation=relu, solver=adam,
  max_iter=500, early_stopping=True, validation_fraction=0.1, random_state=2
**Metrik:** Top-1/3/5 Accuracy, Macro Precision/Recall/F1, Weighted F1, MCC, ROC-AUC

---

## Tabel Hasil MLP

| Method | Resampler | Top-1 Acc | Top-3 Acc | Top-5 Acc | Macro Prec | Macro Rec | Macro F1 | Weighted F1 | MCC | ROC-AUC |
|--------|-----------|-----------|-----------|-----------|------------|-----------|----------|-------------|-----|---------|
| MLP (10,10,10) | SMOTE | 23.04% | 57.16% | 72.65% | 10.67% | 21.92% | 12.66% | 13.15% | 0.1842 | 0.7534 |

---

## Detail Konfigurasi

```python
MLPClassifier(
    hidden_layer_sizes=(10, 10, 10),
    activation='relu',
    solver='adam',
    max_iter=500,
    random_state=2,
    early_stopping=True,
    validation_fraction=0.1,
)
```

---

## Confusion Matrix

![Confusion Matrix — MLP](confusion_matrix_mlp.png)
