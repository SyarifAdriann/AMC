import pickle, numpy as np
from pathlib import Path
from sklearn.tree import _tree

ROOT = Path('c:/xampp/htdocs/AMC')
with open(ROOT / 'ml/encoders_redo.pkl', 'rb') as f:
    encoders = pickle.load(f)
with open(ROOT / 'ml/parking_stand_model_rf_redo.pkl', 'rb') as f:
    model = pickle.load(f)

stand_classes = list(encoders['parking_stand'].classes_)
model_classes = list(model.classes_)

tree0 = model.estimators_[0]
t = tree0.tree_

# Key insight: t.value is NORMALIZED (proportions, sums to 1 per node).
# To recover counts: multiply by n_node_samples
def get_counts(t, node_id):
    n = int(t.n_node_samples[node_id])
    props = t.value[node_id][0]   # normalized, sums to ~1
    counts = np.round(props * n).astype(int)
    return counts, n

print("=== VERIFICATION with multiplied counts ===")
counts0, n0 = get_counts(t, 0)
print(f"Root: n_node_samples={n0},  counts_sum={counts0.sum()}")
for ci, cnt in enumerate(counts0):
    if cnt > 0:
        stand_int  = model_classes[ci]
        stand_name = stand_classes[stand_int]
        print(f"  Stand {stand_name}: {cnt} samples, p={cnt/n0:.6f}")

# Gini manual check
props = counts0 / counts0.sum()
gini_manual = 1.0 - np.sum(props**2)
print(f"\nGini manual = {gini_manual:.4f}  (model says: {t.impurity[0]:.4f})")

# Leaf node check
leaf_node = 143
counts_leaf, n_leaf = get_counts(t, leaf_node)
print(f"\nLeaf (Node {leaf_node}): n={n_leaf}, counts_sum={counts_leaf.sum()}")
for ci, cnt in enumerate(counts_leaf):
    if cnt > 0:
        stand_int  = model_classes[ci]
        stand_name = stand_classes[stand_int]
        print(f"  Stand {stand_name}: {cnt} samples, p={cnt/n_leaf:.6f}")
props_leaf = counts_leaf / counts_leaf.sum()
gini_leaf  = 1.0 - np.sum(props_leaf**2)
print(f"Gini leaf manual = {gini_leaf:.4f}  (model says: {t.impurity[leaf_node]:.4f})")
