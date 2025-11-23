import textwrap
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

WIDTH, HEIGHT = 1600, 900
BG_COLOR = (248, 250, 252)
BOX_BG = (255, 255, 255)
BOX_BORDER = (79, 70, 229)
TEXT_COLOR = (15, 23, 42)
ARROW_COLOR = (30, 64, 175)
TITLE_COLOR = (2, 132, 199)

font = ImageFont.load_default()
title_font = ImageFont.load_default()

img = Image.new('RGB', (WIDTH, HEIGHT), BG_COLOR)
draw = ImageDraw.Draw(img)

def draw_box(x, y, w, h, title, lines):
    draw.rounded_rectangle([x, y, x + w, y + h], radius=16, fill=BOX_BG, outline=BOX_BORDER, width=2)
    draw.text((x + 16, y + 12), title, fill=TITLE_COLOR, font=title_font)
    text_y = y + 40
    for line in lines:
        wrapped = textwrap.wrap(line, width=38)
        for wrapped_line in wrapped:
            draw.text((x + 16, text_y), wrapped_line, fill=TEXT_COLOR, font=font)
            text_y += 14
        text_y += 6

flow = [
    (60, 100, 280, 260, "Operator UI", [
        "1. Open stand modal & enter flight details",
        "2. Click 'Get AI Recommendations'",
        "3. Review ranked cards and pick stand",
        "4. Save assignment (AI toast feedback)"
    ]),
    (380, 60, 320, 320, "PHP Backend", [
        "ApronController@recommend validates payload",
        "callPythonPredictor streams JSON to Python",
        "applyBusinessRules merges availability + airline prefs",
        "recordPredictionLog persists raw predictions",
        "saveMovement stores assignment & marks accuracy"
    ]),
    (740, 60, 280, 260, "Python Runtime", [
        "ml/predict.py loads model + encoders",
        "builds feature vector",
        "model.predict_proba() -> top-3",
        "returns JSON back to PHP"
    ]),
    (1060, 60, 320, 320, "MySQL", [
        "aircraft_movements",
        "airline_preferences",
        "ml_prediction_log",
        "ml_model_versions"
    ]),
    (740, 360, 640, 220, "Monitoring & Dashboard", [
        "/api/ml/metrics aggregates last 30 days",
        "Dashboard widgets show observed accuracy",
        "Prediction logbook filters hit/miss/pending",
        "Ops team monitors retraining readiness"
    ])
]

for box in flow:
    draw_box(*box)

arrow_paths = [
    ((340, 190), (380, 190)),  # Operator -> PHP
    ((700, 190), (740, 190)),  # PHP -> Python
    ((1020, 190), (1060, 190)),  # Python -> DB
    ((540, 380), (540, 480)),  # PHP -> Monitoring
    ((1220, 380), (1220, 480)),  # DB -> Monitoring
]

for start, end in arrow_paths:
    draw.line([start, end], fill=ARROW_COLOR, width=4)
    arrow_tip = end
    if start[0] == end[0]:  # vertical
        direction = 1 if end[1] > start[1] else -1
        draw.polygon([
            (arrow_tip[0] - 8, arrow_tip[1] - 10 * direction),
            (arrow_tip[0] + 8, arrow_tip[1] - 10 * direction),
            (arrow_tip[0], arrow_tip[1])
        ], fill=ARROW_COLOR)
    else:  # horizontal
        direction = 1 if end[0] > start[0] else -1
        draw.polygon([
            (arrow_tip[0] - 10 * direction, arrow_tip[1] - 8),
            (arrow_tip[0] - 10 * direction, arrow_tip[1] + 8),
            (arrow_tip[0], arrow_tip[1])
        ], fill=ARROW_COLOR)

header_text = "AMC ML Integration Workflow"
text_width = draw.textlength(header_text, font=title_font)
draw.text(((WIDTH - text_width) / 2, 20), header_text, fill=TEXT_COLOR, font=title_font)

dest = Path('reports/phase12_workflow.png')
dest.parent.mkdir(parents=True, exist_ok=True)
img.save(dest)
print(f"Saved diagram to {dest}")
