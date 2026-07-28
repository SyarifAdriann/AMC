# Laporan Performa Model — AMC Parking Stand Prediction
## *Model Performance Report*

**Model:** Random Forest Classifier  
**Tanggal Training:** 16 April 2026  
**Dataset:** `parking_history_encoded_redo.csv`  
**Total Data:** 5.190 baris | **Train:** 4.152 | **Test:** 1.038  
**Jumlah Kelas (Parking Stand):** 17 stand  
**Split:** 80% train / 20% test, stratified, random_state=42  

---

## 1. Hasil Hyperparameter Terbaik (GridSearchCV)

Proses pencarian hyperparameter menggunakan **GridSearchCV** dengan **5-fold cross-validation** dan **24 kombinasi parameter** (total 120 fitting). Hasilnya:

| Parameter | Nilai Terbaik |
|-----------|---------------|
| `n_estimators` | 200 |
| `max_depth` | None (unlimited) |
| `min_samples_leaf` | 5 |
| `min_samples_split` | 2 |
| `class_weight` | balanced_subsample |

**Best CV Score (5-fold):** `38.70%`

---

## 2. Ringkasan Metrik Utama

| Metrik | Nilai | Keterangan |
|--------|-------|------------|
| **Top-1 Accuracy (Test)** | **36.32%** | Prediksi pertama tepat |
| **Top-3 Accuracy (Test)** | **80.35%** | Stand benar ada di 3 rekomendasi ✅ |
| **Top-5 Accuracy (Test)** | **98.94%** | Stand benar ada di 5 rekomendasi |
| Train Accuracy (Top-1) | 41.62% | Akurasi di data latih |
| Baseline Accuracy | 10.89% | Tebak selalu stand terbanyak |
| Best CV Score | 38.70% | Rata-rata 5-fold CV |

> **Catatan penting:** Metrik paling relevan secara operasional adalah **Top-3 Accuracy (80.35%)**, karena sistem menampilkan 3 rekomendasi kepada pengguna untuk dipilih.

---

## 3. Penjelasan Setiap Metrik

---

### 3.1 Top-K Accuracy

**Definisi:**  
Top-K Accuracy mengukur seberapa sering label yang benar (parking stand aktual) muncul di dalam K prediksi teratas model, diurutkan berdasarkan probabilitas tertinggi.

**Formula:**
$$\text{Top-K Accuracy} = \frac{\text{Jumlah sampel di mana label benar ada di K teratas}}{\text{Total sampel test}}$$

**Hasil:**

| K | Nilai | Interpretasi |
|---|-------|--------------|
| Top-1 | 36.32% | Prediksi #1 model == stand aktual (paling ketat) |
| Top-3 | 80.35% | Stand aktual ada di salah satu dari 3 prediksi model |
| Top-5 | 98.94% | Stand aktual ada di salah satu dari 5 prediksi model |

**Mengapa Top-1 rendah (36%) tetapi Top-3 tinggi (80%)?**  
Dari 17 parking stand, banyak stand yang digunakan secara bergantian oleh airline yang sama (contoh: BATIK AIR bisa memakai A2, A3, B1, B7, B8, dst. tergantung kondisi apron). Model "tahu" stand-stand mana yang mungkin, namun sulit menentukan satu stand yang paling tepat. Meskipun begitu, stand yang benar hampir selalu masuk dalam 3 besar.

---

### 3.2 Train Accuracy vs Test Accuracy

**Definisi:**  
- **Train Accuracy:** Akurasi Top-1 model pada data yang digunakan untuk melatihnya.  
- **Test Accuracy:** Akurasi Top-1 model pada data yang **belum pernah dilihat** sebelumnya (data test).

**Hasil:**

| | Nilai |
|-|-------|
| Train Accuracy | 41.62% |
| Test Accuracy  | 36.32% |
| **Selisih (Gap)**  | **5.30%** |

**Interpretasi:**  
Gap yang kecil (5.30%) menunjukkan model **tidak mengalami overfitting** secara signifikan. Model tidak hanya "menghafal" data latih, melainkan mampu menggeneralisasi ke data baru dengan baik.

---

### 3.3 Baseline Accuracy

**Definisi:**  
Akurasi yang diperoleh apabila model selalu memprediksi **kelas yang paling sering muncul** di data latih, tanpa memperhatikan input sama sekali.

**Hasil:** `10.89%`  
*(Stand paling populer adalah B1 yang muncul ~10.89% dari total data test)*

**Perbandingan dengan model:**

| | Akurasi | Rasio vs Baseline |
|-|---------|-------------------|
| Baseline | 10.89% | 1.0× |
| Model Top-1 | 36.32% | **3.33×** lebih baik |
| Model Top-3 | 80.35% | **7.38×** lebih baik |

---

### 3.4 Precision

**Definisi:**  
Dari semua prediksi model untuk stand X, berapa persen yang **benar-benar merupakan stand X**?

**Formula:**
$$\text{Precision} = \frac{TP}{TP + FP}$$

- **TP (True Positive):** Model memprediksi stand X dan memang benar X
- **FP (False Positive):** Model memprediksi stand X tetapi sebenarnya bukan X

**Analogi:** Jika model berkata "ini Stand B2" — seberapa sering itu benar?

**Hasil:**

| Averaging | Nilai | Penjelasan |
|-----------|-------|------------|
| Macro | 35.64% | Rata-rata precision setiap stand, bobot sama |
| Weighted | 36.96% | Rata-rata precision, dibobot jumlah sampel per stand |

---

### 3.5 Recall

**Definisi:**  
Dari semua kejadian yang **sebenarnya menggunakan stand X**, berapa persen yang berhasil ditangkap oleh model?

**Formula:**
$$\text{Recall} = \frac{TP}{TP + FN}$$

- **FN (False Negative):** Stand X digunakan, tapi model memprediksi stand lain

**Analogi:** Dari semua penerbangan yang seharusnya parkir di B2 — berapa yang berhasil diprediksi model sebagai B2?

**Hasil:**

| Averaging | Nilai | Penjelasan |
|-----------|-------|------------|
| Macro | 38.74% | Rata-rata recall setiap stand, bobot sama |
| Weighted | 36.32% | Rata-rata recall, dibobot jumlah sampel per stand |

---

### 3.6 F1-Score

**Definisi:**  
F1-Score adalah **rata-rata harmonik** antara Precision dan Recall. Ini adalah metrik tunggal yang menyeimbangkan keduanya — model dengan precision tinggi tapi recall rendah (atau sebaliknya) akan mendapat F1 yang rendah.

**Formula:**
$$\text{F1} = 2 \times \frac{\text{Precision} \times \text{Recall}}{\text{Precision} + \text{Recall}}$$

**Hasil:**

| Averaging | Nilai | Penjelasan |
|-----------|-------|------------|
| Macro | 33.51% | Rata-rata F1 setiap stand, bobot sama |
| Weighted | 32.96% | Rata-rata F1, dibobot jumlah sampel per stand |

**Kapan pakai Macro vs Weighted?**

| | Macro | Weighted |
|-|-------|----------|
| **Kegunaan** | Evaluasi performa model pada stand yang jarang digunakan | Evaluasi performa secara keseluruhan sesuai distribusi data nyata |
| **Konteks AMC** | Memastikan model tidak mengabaikan stand minoritas (mis. A0) | Mencerminkan pengalaman pengguna sehari-hari |

---

## 4. Performa Per-Kelas (Per Parking Stand)

Berikut adalah breakdown Top-1 precision, recall, dan F1 untuk masing-masing dari 17 parking stand:

| Stand | Precision | Recall | F1-Score | Support (n test) |
|-------|-----------|--------|----------|-----------------|
| **A0** | 0.58 | **1.00** | **0.73** | 18 |
| **A1** | 0.21 | **0.83** | 0.33 | 65 |
| A2 | 0.25 | 0.01 | 0.02 | 86 |
| A3 | 0.11 | 0.05 | 0.07 | 100 |
| B1 | **0.51** | 0.33 | 0.40 | 113 |
| B10 | 0.26 | 0.15 | 0.19 | 33 |
| B11 | 0.23 | 0.12 | 0.16 | 40 |
| B12 | 0.30 | 0.70 | 0.42 | 43 |
| B13 | 0.27 | 0.10 | 0.14 | 41 |
| **B2** | **0.69** | 0.47 | **0.56** | 91 |
| B3 | 0.53 | 0.42 | 0.47 | 73 |
| B4 | 0.45 | 0.62 | 0.52 | 76 |
| B5 | 0.40 | 0.27 | 0.32 | 62 |
| B6 | 0.18 | 0.14 | 0.16 | 51 |
| **B7** | 0.47 | **0.59** | **0.52** | 64 |
| **B8** | 0.49 | **0.72** | **0.58** | 46 |
| B9 | 0.12 | 0.06 | 0.08 | 36 |
| **Macro Avg** | **0.36** | **0.39** | **0.34** | 1038 |
| **Weighted Avg** | **0.37** | **0.36** | **0.33** | 1038 |

**Observasi:**
- **A0 & A1** punya recall tinggi → model sangat konsisten mengenali pesawat kecil dan stand A-zone
- **A2 & A3** recall sangat rendah → stand ini sering tertukar (banyak airline berbeda pakai A2/A3)
- **B2 & B8** punya F1 tertinggi → pola penggunaan paling konsisten di zona B

---

## 5. Feature Importance

Berikut kontribusi setiap fitur dalam pengambilan keputusan model:

| Fitur | Kepentingan | Bar |
|-------|-------------|-----|
| Stand Zone | 38.16% | `████████████████████████████████████` |
| Operator Airline | 21.45% | `█████████████████████` |
| Aircraft Type | 19.04% | `███████████████████` |
| Category | 11.17% | `███████████` |
| Aircraft Size | 7.41% | `███████` |
| Airline Tier | 2.77% | `███` |

**Interpretasi:**
- **Stand Zone (38%)** adalah fitur paling berpengaruh — zona apron (kargo/charter/komersial) sangat menentukan stand
- **Operator Airline (21%)** — setiap airline cenderung memiliki preferensi stand tertentu
- **Aircraft Type (19%)** — kompatibilitas fisik pesawat dengan stand sangat relevan
- **Category (11%)** — kategori operasi (komersial/kargo/charter) mempersempit pilihan zone
- **Aircraft Size & Airline Tier** — kontribusi kecil namun tetap membantu di edge cases

---

## 6. Kesimpulan Performa

| Aspek | Penilaian | Keterangan |
|-------|-----------|------------|
| **Akurasi Operasional (Top-3)** | ✅ Sangat Baik | 80.35% — melampaui target 75% |
| **Overfitting** | ✅ Minimal | Gap train-test hanya 5.30% |
| **Peningkatan vs Baseline** | ✅ Signifikan | 3.3× (Top-1) dan 7.4× (Top-3) |
| **Kelengkapan (Top-5)** | ✅ Hampir Sempurna | 98.94% |
| **F1-Score Macro** | ⚠️ Moderat | 33.51% — rendah karena stand A2/A3/B9 sulit diprediksi |

> **Kesimpulan:** Model berhasil memenuhi target utama sistem yaitu **Top-3 Accuracy ≥ 75%** dengan hasil **80.35%**. Rendahnya Top-1 accuracy bukan indikasi model yang buruk, melainkan mencerminkan **ambiguitas inheren** dalam penempatan pesawat di apron yang bergantung pada banyak faktor dinamis (ketersediaan stand, kondisi operasional, dll.) yang tidak tercakup dalam fitur model.
