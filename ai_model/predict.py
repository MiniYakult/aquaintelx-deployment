import warnings
warnings.filterwarnings("ignore")

import pandas as pd
import joblib
import sys

model = joblib.load("water_quality_model.pkl")
encoder = joblib.load("label_encoder.pkl")

temperature = float(sys.argv[1])
ph = float(sys.argv[2])
turbidity = float(sys.argv[3])
tds = float(sys.argv[4])

sample = pd.DataFrame([{
    "temperature": temperature,
    "ph": ph,
    "turbidity": turbidity,
    "tds": tds
}])

prediction = model.predict(sample)
risk = encoder.inverse_transform(prediction)[0]

print(risk)