# 🔍 AUDIT FAKTUAL SLIDE SIDANG vs KODE/ARTEFAK AKTUAL

**Tanggal audit:** 2026-07-20  
**Mode:** Read-only, TIDAK ada file yang diubah  
**Metode:** Inspeksi langsung terhadap `.pkl`, script `.py`, file `.php`, dataset `.csv`, dan JSON

---

## TABEL RINGKASAN

| # | Poin Audit | Klaim Slide | Nilai Aktual | Status |
|---|-----------|-------------|-------------|--------|
| 1 | `n_estimators` | 200 | **200** (model .pkl + semua script) | ✅ **MATCH** |
| 2a | Feature importance #1 | `stand_zone` ~37,58% | **38.16%** (`0.381599`) | ⚠️ **MISMATCH MINOR** |
| 2b | Feature importance #2 | `aircraft_type` ~20,36% | **`operator_airline` 21.45%** | ❌ **MISMATCH** |
| 3 | Composite Scoring 0.6/0.4 | Ada di implementasi | **ADA** (PHP + Python) | ✅ **MATCH** |
| 4 | Response time | 7–8 detik | ~4 detik (cold) / ~0.5 detik (warm) | ❌ **MISMATCH** |
| 5a | Dataset raw | Belum diketahui | **6.075 baris** | ✅ **DITEMUKAN** |
| 5b | Dataset clean | 4.069 | **4.069 baris** (clean) / **5.190** (encoded) | ⚠️ **PERLU KLARIFIKASI** |
| 6 | Encoding | LabelEncoder semua | **LabelEncoder semua (7 encoder)** | ✅ **MATCH** |
| 7a | SMOTE hanya data latih? | Ya | **Ya** | ✅ **MATCH** |
| 7b | test_size | - | **0.20** | ✅ **INFO** |
| 7c | stratify | - | **stratify=y** | ✅ **INFO** |
| 7d | random_state | - | **42** | ✅ **INFO** |
| 7e | class_weight | - | **`balanced_subsample`** | ✅ **INFO** |

---

## DETAIL PER POIN

---

### 1. n_estimators Random Forest

> **KLAIM SLIDE:** 200 (dengan indikasi 100 di tempat lain)

**HASIL: ✅ DEFINITIF = 200**

#### Bukti dari model .pkl yang di-load:

```
python -c "import joblib; m=joblib.load('ml/parking_stand_model_rf_redo.pkl'); print(m.n_estimators, len(m.estimators_))"
```

**Output:**
```
n_estimators: 200
len(estimators_): 200
len(classes_): 17
n_features_in_: 6
```

Model `.pkl` berisi **200 pohon yang benar-benar ter-fit** (`len(estimators_) == 200`). Tidak ada GridSearchCV wrapper — ini langsung `RandomForestClassifier`.

#### Bukti dari kode training:

| File | Baris | Nilai |
|------|-------|-------|
| [results_summary_redo.json](file:///c:/xampp/htdocs/AMC/ml/results_summary_redo.json#L21) | 21 | `"n_estimators": 200` |
| [retrain_per_stand_metrics.py](file:///c:/xampp/htdocs/AMC/ml/retrain_per_stand_metrics.py#L56) | 56 | `n_estimators = 200` |
| [pipeline_verify_and_variation.py](file:///c:/xampp/htdocs/AMC/ml/pipeline_verify_and_variation.py#L49) | 49 | `'n_estimators': 200` |
| [build_v4_md.py](file:///c:/xampp/htdocs/AMC/ml/build_v4_md.py#L9) | 9 | `N_TREES = 200` |

#### "Indikasi 100" — RESOLVED:

Satu-satunya kemunculan `n_estimators` + `100` adalah di [comparison_experiments.py L320](file:///c:/xampp/htdocs/AMC/ml/comparison_experiments.py#L320):

```python
'n_estimators': [50, 100, 200],   # Ini adalah param_grid untuk GridSearchCV
```

Ini adalah **daftar kandidat** untuk eksperimen perbandingan AlBassam, **bukan** konfigurasi model produksi. GridSearchCV memilih **200** sebagai best parameter.

#### `best_params_` dari model .pkl:

Model yang tersimpan **TIDAK** memiliki atribut `best_params_` (bukan objek GridSearchCV). Parameter optimal tersimpan terpisah di [results_summary_redo.json](file:///c:/xampp/htdocs/AMC/ml/results_summary_redo.json):

```json
"best_params": {
    "class_weight": "balanced_subsample",
    "max_depth": null,
    "min_samples_leaf": 5,
    "min_samples_split": 2,
    "n_estimators": 200
}
```

---

### 2. Feature Importances — Ranking & Nilai Persis

> **KLAIM SLIDE:** `stand_zone` paling penting ~37,58%, `aircraft_type` ~20,36%  
> **POSTER LAMA:** `aircraft_type` 42%

**HASIL: ⚠️ MISMATCH pada detail**

#### Nilai aktual dari model .pkl:

```
python -c "import joblib; m=joblib.load('ml/parking_stand_model_rf_redo.pkl'); ..."
```

| Rank | Fitur | Importance | Persentase |
|------|-------|-----------|-----------|
| **1** | **stand_zone** | 0.381599 | **38.16%** |
| **2** | **operator_airline** | 0.214546 | **21.45%** |
| **3** | **aircraft_type** | 0.190355 | **19.04%** |
| **4** | category | 0.111698 | 11.17% |
| **5** | aircraft_size | 0.074118 | 7.41% |
| **6** | airline_tier | 0.027683 | 2.77% |

**Sumber:** [results_summary_redo.json L26-33](file:///c:/xampp/htdocs/AMC/ml/results_summary_redo.json#L26)  
**Identik** dengan output `m.feature_importances_` langsung dari model .pkl.

#### Analisis klaim:

| Klaim | Aktual | Status |
|-------|--------|--------|
| `stand_zone` paling penting ✓ | Rank #1 | ✅ MATCH |
| `stand_zone` ~37,58% | **38.16%** | ⚠️ MISMATCH MINOR (selisih 0.58 pp) |
| `aircraft_type` ~20,36% (rank #2?) | **19.04%** (rank **#3**) | ❌ **MISMATCH** |
| `aircraft_type` 42% (poster lama) | 19.04% | ❌ **MISMATCH BESAR** — poster lama SALAH |

> [!CAUTION]
> **Fitur rank #2 adalah `operator_airline` (21.45%), BUKAN `aircraft_type` (19.04%).** Jika slide mengatakan `aircraft_type` adalah fitur ke-2 terpenting, itu **SALAH**. Poster lama yang mengklaim `aircraft_type` 42% kemungkinan merujuk pada model/data yang berbeda dan **tidak boleh digunakan**.

---

### 3. Composite Scoring (0.6 × probability + 0.4 × preference)

> **KLAIM:** Composite scoring menggabungkan probabilitas ML dengan skor preferensi

**HASIL: ✅ ADA — terkonfirmasi di 3 lokasi kode produksi**

#### Lokasi kode:

**1. PHP Production — [RecommendationService.php L318](file:///c:/xampp/htdocs/AMC/app/Services/RecommendationService.php#L318):**

```php
$score = (0.6 * $probability) + (0.4 * $normalizedPreference);
```

**2. Python CLI — [predictbatch.py L46-47](file:///c:/xampp/htdocs/AMC/ml/predictbatch.py#L46) dan [L240](file:///c:/xampp/htdocs/AMC/ml/predictbatch.py#L240):**

```python
PROB_WEIGHT = 0.6
PREF_WEIGHT = 0.4
# ...
composite = PROB_WEIGHT * prob + PREF_WEIGHT * norm_pref
```

**3. Unit Test — [ApronControllerTest.php L233-237](file:///c:/xampp/htdocs/AMC/tests/ApronControllerTest.php#L233):**

```php
$this->assertSame('B9', $candidates[0]['stand'], 'Top candidate should be B9 with highest composite score');
```

#### Rumus lengkap:

```
composite_score = 0.6 × model_probability + 0.4 × (preference_score / 100)
```

Dimana:
- `model_probability` = output `predict_proba` dari Random Forest
- `preference_score` = skor 0–100 dari tabel `airline_preferences` atau fallback historis
- `normalizedPreference = max(0, min(1, preference / 100))`
- Ranking: sort descending by `composite_score`, tiebreak by `probability`

---

### 4. Response/Inference Time

> **KLAIM SLIDE:** 7–8 detik

**HASIL: ❌ MISMATCH — tidak ada pengukuran instrumentasi terukur (log/benchmark) yang menyatakan 7-8 detik**

#### Yang ditemukan dalam kode:

| Sumber | Klaim | Lokasi |
|--------|-------|--------|
| [results_defense.md L17](file:///c:/xampp/htdocs/AMC/results_defense.md#L17) | "~7-8 seconds" | Dokumen persiapan defense |
| [results_defense.md L88](file:///c:/xampp/htdocs/AMC/results_defense.md#L88) | "7 to 8 seconds" | Sama |
| [content.md L34](file:///c:/xampp/htdocs/AMC/skripsi%20bab%204%205/content.md#L34) | "~4 detik" | Bab 4 skripsi |
| [content.md L105](file:///c:/xampp/htdocs/AMC/skripsi%20bab%204%205/content.md#L105) | "Cold Start: ~4.0 detik" | Bab 4 skripsi |
| [content.md L106](file:///c:/xampp/htdocs/AMC/skripsi%20bab%204%205/content.md#L106) | "Warm: ~0.5 detik" | Bab 4 skripsi |
| [outline.md L121](file:///c:/xampp/htdocs/AMC/skripsi%20bab%204%205/outline.md#L121) | "~4.0 Detik (Cold), ~0.5 Detik (Warm)" | Tabel outline |
| [prefilledcontent.md L54](file:///c:/xampp/htdocs/AMC/skripsi%20bab%204%205/prefilledcontent.md#L54) | "~4 detik end-to-end" | Tabel prefilled |
| [RecommendationService.php L124](file:///c:/xampp/htdocs/AMC/app/Services/RecommendationService.php#L124) | `$timeoutSeconds = 6` | Timeout parameter |
| [phase10_uat_checklist.md L48](file:///c:/xampp/htdocs/AMC/reports/pipeline/phase10_uat_checklist.md#L48) | "< 2.0 s target" | UAT checklist |

> [!WARNING]
> **Tidak ada log instrumentasi, benchmark script, atau pengukuran `time()` end-to-end yang tersimpan.** Semua angka yang ada berasal dari **dokumen/markdown** saja (bukan kode terukur).
>
> Angka "7-8 detik" hanya muncul di `results_defense.md` (dokumen persiapan defense). Mayoritas sumber lain (bab 4 skripsi, outline, prefilled) mengklaim **~4 detik (cold start)**. Ada inkonsistensi antar dokumen sendiri.
>
> Timeout PHP di-set 6 detik (`$timeoutSeconds = 6`), jadi respons 7-8 detik akan **menyebabkan timeout error**.

---

### 5. Dataset — Jumlah Baris

> **KLAIM:** Clean = 4.069, Raw = belum diketahui

**HASIL: ✅ Clean MATCH, Raw DITEMUKAN**

```
python -c "import pandas as pd; ..."
```

| File | Path | Jumlah Baris |
|------|------|-------------|
| **Dataset RAW** | [DATASET AMC .csv](file:///c:/xampp/htdocs/AMC/DATASET%20AMC%20.csv) | **6.075** |
| Fields used (4 kolom) | [DATASET_AMC_fields_used.csv](file:///c:/xampp/htdocs/AMC/DATASET_AMC_fields_used.csv) | **6.075** |
| **Clean** | [parking_history_clean.csv](file:///c:/xampp/htdocs/AMC/data/parking_history_clean.csv) | **4.069** ✅ |
| Encoded (setelah feature eng.) | [parking_history_encoded_redo.csv](file:///c:/xampp/htdocs/AMC/data/parking_history_encoded_redo.csv) | **5.190** |

> [!IMPORTANT]
> Ada **dua** angka "clean" yang perlu dibedakan di slide:
> - **4.069** = `parking_history_clean.csv` (setelah cleaning awal, hanya 3 kolom asli valid)
> - **5.190** = `parking_history_encoded_redo.csv` (setelah feature engineering + filter ke 17 stand valid) — ini yang dipakai di training
>
> Klaim "4.069 clean" ✅ cocok. Tapi data **yang benar-benar dilatih** = **5.190 baris** (train 4.152 + test 1.038, dari [results_summary_redo.json L23-24](file:///c:/xampp/htdocs/AMC/ml/results_summary_redo.json#L23)).

---

### 6. Encoding — Jenis Encoder

> **KLAIM:** Label Encoding untuk semua 6 fitur

**HASIL: ✅ MATCH — semua LabelEncoder**

```
python -c "import pickle; enc = pickle.load(open('ml/encoders_redo.pkl','rb')); ..."
```

| Encoder Key | Tipe | Contoh Classes |
|-------------|------|---------------|
| `aircraft_type` | **LabelEncoder** | A 320, A 340, A320, ATR 42, ATR 72 ... |
| `aircraft_size` | **LabelEncoder** | SMALL_A0_COMPATIBLE, STANDARD |
| `operator_airline` | **LabelEncoder** | AFM, AIR PASIFIC, AIRNESIA, AMM, B.B.N. ... |
| `airline_tier` | **LabelEncoder** | HIGH_FREQUENCY, LOW_FREQUENCY, MEDIUM_FREQUENCY |
| `category` | **LabelEncoder** | CARGO, CHARTER, COMMERCIAL |
| `stand_zone` | **LabelEncoder** | LEFT_CARGO, MIDDLE_CHARTER, RIGHT_COMMERCIAL |
| `parking_stand` (target) | **LabelEncoder** | A0, A1, A2, A3, B1 ... |

**Sumber:** [encoders_redo.pkl](file:///c:/xampp/htdocs/AMC/ml/encoders_redo.pkl) — 7 `LabelEncoder` objects, **tidak ada** OneHotEncoder/get_dummies.

Penggunaan di inference: [predict.py L61-72](file:///c:/xampp/htdocs/AMC/ml/predict.py#L61) — fungsi `to_index()` menggunakan `encoder.classes_` lookup.

---

### 7. BONUS — SMOTE, train_test_split, class_weight

#### 7a. SMOTE hanya di data latih?

**✅ YA — terkonfirmasi.**

[retrain_per_stand_metrics.py L42-51](file:///c:/xampp/htdocs/AMC/ml/retrain_per_stand_metrics.py#L42):

```python
X_train, X_test, y_train, y_test = train_test_split(...)   # L42-44
smote = SMOTE(random_state=42)                               # L49
X_train_sm, y_train_sm = smote.fit_resample(X_train, y_train) # L50  ← HANYA X_train
```

SMOTE diterapkan **setelah** split, hanya pada `X_train`/`y_train`. `X_test`/`y_test` **tidak tersentuh**.

Pola yang sama juga ada di:
- [pipeline_verify_and_variation.py L148-155](file:///c:/xampp/htdocs/AMC/ml/pipeline_verify_and_variation.py#L148)
- [j48_baseline.py L66-70](file:///c:/xampp/htdocs/AMC/ml/j48_baseline.py#L66)
- [comparison_experiments.py L362-364](file:///c:/xampp/htdocs/AMC/ml/comparison_experiments.py#L362) (resampler hanya pada train)

#### 7b-d. train_test_split parameters:

[retrain_per_stand_metrics.py L42-44](file:///c:/xampp/htdocs/AMC/ml/retrain_per_stand_metrics.py#L42):

```python
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.20, random_state=42, stratify=y
)
```

| Parameter | Nilai |
|-----------|-------|
| `test_size` | **0.20** (80/20 split) |
| `random_state` | **42** |
| `stratify` | **y** (stratified) |

Konsisten di semua script training:
- [pipeline_verify_and_variation.py L223-224](file:///c:/xampp/htdocs/AMC/ml/pipeline_verify_and_variation.py#L223): `test_size=TEST_SIZE (0.2), random_state=RANDOM_STATE (42), stratify=y_full`
- [comparison_experiments.py L305-307](file:///c:/xampp/htdocs/AMC/ml/comparison_experiments.py#L305): `test_size=0.20, random_state=42, stratify=y_arr`

#### 7e. class_weight di model:

[retrain_per_stand_metrics.py L60](file:///c:/xampp/htdocs/AMC/ml/retrain_per_stand_metrics.py#L60):

```python
class_weight = 'balanced_subsample'
```

Terkonfirmasi juga di [results_summary_redo.json L17](file:///c:/xampp/htdocs/AMC/ml/results_summary_redo.json#L17):

```json
"class_weight": "balanced_subsample"
```

---

## ❌ DAFTAR MISMATCH / PERLU PERHATIAN

| # | Poin | Detail | Tingkat |
|---|------|--------|---------|
| **M1** | Feature importance rank #2 | Slide klaim `aircraft_type` ~20,36% → Aktual: **`operator_airline` 21.45%** adalah rank #2, `aircraft_type` adalah rank **#3** (19.04%) | 🔴 **HARUS DIPERBAIKI** |
| **M2** | Feature importance `stand_zone` persentase | Klaim ~37,58% → Aktual **38.16%** | 🟡 Minor, bisa dibiarkan |
| **M3** | Poster lama `aircraft_type` 42% | Nilai aktual 19.04% → poster lama **SALAH TOTAL** | 🔴 **Jangan gunakan** |
| **M4** | Response time 7–8 detik | Tidak ada instrumentasi terukur. Mayoritas dokumen lain klaim ~4 detik. Timeout PHP = 6 detik (7-8 detik → timeout!) | 🔴 **INKONSISTEN** |
| **M5** | Dataset "clean" — ambiguitas | 4.069 benar untuk `parking_history_clean.csv`, tapi data training sebenarnya dari **5.190** baris (encoded) | 🟡 **Perlu klarifikasi di slide** |

---

## ✅ ITEM YANG TERKONFIRMASI BENAR

| # | Poin | Detail |
|---|------|--------|
| C1 | n_estimators = 200 | Definitif dari model .pkl (200 trees ter-fit) |
| C2 | stand_zone = fitur terpenting | Rank #1 konsisten |
| C3 | Composite scoring 0.6/0.4 ADA | Di PHP + Python + unit test |
| C4 | Label Encoding semua 6 fitur | 7 LabelEncoder di encoders_redo.pkl |
| C5 | SMOTE hanya di data latih | Setelah split, hanya X_train |
| C6 | Dataset raw = 6.075 baris | DATASET AMC .csv |
| C7 | Dataset clean = 4.069 baris | parking_history_clean.csv |
| C8 | test_size=0.20, random_state=42, stratify=y | Konsisten di semua script |
| C9 | class_weight='balanced_subsample' | Di model + JSON config |
