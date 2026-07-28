import json
import subprocess
import sys
import unittest
from pathlib import Path

from .predict import build_feature_vector


class BuildFeatureVectorTests(unittest.TestCase):
    def test_normalizes_required_fields(self):
        payload = {
            'aircraft_type': 'b738',
            'operator_airline': 'garuda indonesia',
            'category': 'komersial',
        }
        features = build_feature_vector(payload)
        self.assertEqual(features['aircraft_type'], 'B738')
        self.assertEqual(features['operator_airline'], 'GARUDA INDONESIA')
        self.assertEqual(features['category'], 'Komersial')
        self.assertEqual(features['airline_category'], 'GARUDA INDONESIA|KOMERSIAL')
        self.assertEqual(features['aircraft_airline'], 'B738|GARUDA INDONESIA')
        self.assertEqual(features['aircraft_category'], 'B738|KOMERSIAL')

    def test_missing_required_fields_raise(self):
        with self.assertRaises(ValueError):
            build_feature_vector({'aircraft_type': 'B738'})


class PredictCliTests(unittest.TestCase):
    def setUp(self):
        self.repo_root = Path(__file__).resolve().parents[1]
        self.script = self.repo_root / 'ml' / 'predict.py'

    def _run_predict(self, payload, extra_args=None):
        cmd = [sys.executable, str(self.script)]
        if extra_args:
            cmd.extend(extra_args)
        proc = subprocess.run(
            cmd,
            input=json.dumps(payload).encode('utf-8'),
            capture_output=True,
            cwd=str(self.repo_root),
        )
        stdout = proc.stdout.decode('utf-8').strip()
        stderr = proc.stderr.decode('utf-8').strip()
        self.assertEqual(proc.returncode, 0, msg=stderr or 'predict.py exited with non-zero code')
        self.assertTrue(stdout, 'predict.py returned no stdout')
        return json.loads(stdout)

    def test_cli_smoke(self):
        payload = {
            'aircraft_type': 'B 738',
            'operator_airline': 'GARUDA',
            'category': 'Komersial',
        }
        data = self._run_predict(payload)
        self.assertTrue(data.get('success'), data)
        self.assertGreaterEqual(len(data.get('predictions', [])), 1)

    def test_predictions_are_sorted_and_bounded(self):
        payload = {
            'aircraft_type': 'A320',
            'operator_airline': 'BATIK AIR',
            'category': 'Komersial',
        }
        data = self._run_predict(payload)
        predictions = data.get('predictions', [])
        self.assertEqual(len(predictions), 3, 'default top_k should return 3 predictions')
        probs = [p['probability'] for p in predictions]
        self.assertEqual(probs, sorted(probs, reverse=True))
        for prob in probs:
            self.assertGreaterEqual(prob, 0.0)
            self.assertLessEqual(prob, 1.0)

    def test_top_k_flag_returns_requested_length(self):
        payload = {
            'aircraft_type': 'A320',
            'operator_airline': 'BATIK AIR',
            'category': 'Komersial',
        }
        data = self._run_predict(payload, extra_args=['--top_k', '4'])
        self.assertEqual(len(data.get('predictions', [])), 4)

    def test_unknown_aircraft_type_uses_fallback(self):
        payload = {
            'aircraft_type': 'UNKNOWN_TYPE_12345',
            'operator_airline': 'GARUDA',
            'category': 'Komersial',
        }
        data = self._run_predict(payload)
        self.assertTrue(data.get('success'), data)
        self.assertEqual(len(data.get('predictions', [])), 3)


if __name__ == '__main__':
    unittest.main()
