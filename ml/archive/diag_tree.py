import pickle, numpy as np
from pathlib import Path

ROOT = Path('c:/xampp/htdocs/AMC')
with open(ROOT / 'ml/parking_stand_model_rf_redo.pkl', 'rb') as f:
    model = pickle.load(f)

tree0 = model.estimators_[0]
t = tree0.tree_

print("=== DIAGNOSTIK TREE #0 ===")
print(f"Total nodes:        {t.node_count}")
print(f"Root n_samples:     {t.n_node_samples[0]}")
print(f"Root feature:       {t.feature[0]}")
print(f"Root threshold:     {t.threshold[0]:.4f}")
print(f"Root gini:          {t.impurity[0]:.4f}")
print(f"Root left child:    {t.children_left[0]}")
print(f"Root right child:   {t.children_right[0]}")
print(f"t.value[0] shape:   {t.value[0].shape}")
print(f"t.value[0][0] sum:  {t.value[0][0].sum()}")
print()

print("Root node class distribution (all classes):")
root_values = t.value[0][0]
total = root_values.sum()
for i, count in enumerate(root_values):
    if count > 0:
        print(f"  Class index {i}: {count:.0f} samples, p = {count/total:.6f}")
print(f"  TOTAL: {total:.0f} samples")
print()

# Test decision path for Sample 1
X_test = np.array([[4, 1, 24, 2, 2, 2]])
indicator = tree0.decision_path(X_test)
node_indices = indicator.indices
print(f"Decision path nodes for X=[4,1,24,2,2,2]: {list(node_indices)}")
print()

# Walk the path and show values at each node
print("Path details:")
for nid in node_indices:
    is_leaf = (t.children_left[nid] == -1)
    n = int(t.n_node_samples[nid])
    g = float(t.impurity[nid])
    vals = t.value[nid][0]
    tot  = vals.sum()
    if is_leaf:
        pred = int(np.argmax(vals))
        print(f"  Node {nid:4d} [LEAF]  gini={g:.4f}  n={n}  pred_class={pred}  val_sum={tot:.0f}")
    else:
        feat = int(t.feature[nid])
        thr  = float(t.threshold[nid])
        xv   = int(X_test[0][feat])
        print(f"  Node {nid:4d}         gini={g:.4f}  n={n}  feat={feat}  thr={thr:.4f}  xval={xv}  val_sum={tot:.0f}")
