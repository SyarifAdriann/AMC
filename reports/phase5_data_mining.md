# Phase 5 - Data Mining (Modeling)

**Documented:** 2025-10-24 16:11:19Z

## Modeling Plan
- Train/test split: 80/20 with stratification and random_state=42.
- Classifier: DecisionTreeClassifier with GridSearchCV for hyperparameter tuning.
- Hyperparameter grid: criterion (gini/entropy), max_depth (None,5,10,15), min_samples_split (2,5,10), min_samples_leaf (1,2,4).
- Cross-validation: 5-fold, scoring=accuracy.
- Feature set: encoded aircraft_type, operator_airline, category.
- Target: encoded parking_stand.

## Expected Outputs
- Trained model saved to ml/parking_stand_model.pkl.
- Encoder files (already generated) reused during prediction.
- Feature importance chart saved to reports/phase5_feature_importance.csv and image.
- Metrics summary stored in reports/phase5_metrics.json.

## Next Steps
1. Split dataset and run GridSearchCV.
2. Train final model with best parameters.
3. Evaluate on test set and compute top-3 accuracy.
4. Prepare evaluation visuals and documentation.
