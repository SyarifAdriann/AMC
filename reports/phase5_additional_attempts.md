# Additional Modeling Attempts

| Attempt | Model | Top-3 Accuracy | Notes |
| --- | --- | --- | --- |
| 1 | RandomForest (800 trees, depth=None) | 0.6157 | Improved via ensemble but still <70% |
| 2 | ExtraTrees (1000 trees) | 0.5983 | Similar to RF, no uplift |
| 3 | XGBoost (600 estimators, depth=10) | 0.6070 | Gradient boosting, marginal gain |
| 4 | Balanced RandomForest (600 trees) | 0.5939 | Sampling to balance classes |
| 5 | CatBoost (300 iters) | 0.5852 | Native categorical boosting |
| 6 | HistGradientBoosting | 0.5983 | Histogram-based GBM |
| 7 | RF + XGBoost Ensemble | 0.6157 | Averaged probabilities (best so far) |