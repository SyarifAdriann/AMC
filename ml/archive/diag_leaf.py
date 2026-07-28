import pickle, numpy as np
from pathlib import Path
from sklearn.tree import _tree

ROOT = Path('c:/xampp/htdocs/AMC')
with open(ROOT / 'ml/parking_stand_model_rf_redo.pkl', 'rb') as f:
    model = pickle.load(f)

tree0 = model.estimators_[0]
t = tree0.tree_

# Debug is_leaf for nodes in the path
X = np.array([[4, 1, 24, 2, 2, 2]], dtype=np.float64)
indicator = tree0.decision_path(X)
node_ids = list(indicator.indices)
print(f"Path nodes: {node_ids}")
print()
for nid in node_ids:
    cl = int(t.children_left[nid])
    cr = int(t.children_right[nid])
    feat = int(t.feature[nid])
    thr = float(t.threshold[nid])
    n = int(t.n_node_samples[nid])
    g = float(t.impurity[nid])
    is_leaf_LEAF_const = (cl == _tree.TREE_LEAF)
    is_leaf_minus1 = (cl == -1)
    print(f"  Node {nid:4d}: children_left={cl}  children_right={cr}  "
          f"feature={feat}  thr={thr:.4f}  n={n}  gini={g:.4f}  "
          f"TREE_LEAF={_tree.TREE_LEAF}  is_leaf={is_leaf_LEAF_const}")
