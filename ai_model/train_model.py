import os
import joblib
import pandas as pd

from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix


BASE_DIR = os.path.dirname(os.path.abspath(__file__))

DATASET_PATH = os.path.join(BASE_DIR, "aquaintelx_dataset.csv")
MODEL_PATH = os.path.join(BASE_DIR, "water_quality_model.pkl")
ENCODER_PATH = os.path.join(BASE_DIR, "label_encoder.pkl")

FEATURES = ["temperature", "ph", "turbidity", "tds"]
LABEL_COLUMN = "risk_level"


def main():
    if not os.path.exists(DATASET_PATH):
        raise FileNotFoundError(f"Dataset not found: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)

    print("Dataset loaded.")
    print("Columns:", list(df.columns))
    print("Total rows:", len(df))

    required_columns = FEATURES + [LABEL_COLUMN]

    missing = [col for col in required_columns if col not in df.columns]
    if missing:
        raise ValueError(f"Missing required columns: {missing}")

    df = df[required_columns].copy()

    for col in FEATURES:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    df[LABEL_COLUMN] = df[LABEL_COLUMN].astype(str).str.strip()

    valid_labels = ["Low Risk", "Moderate Risk", "Critical Risk"]
    df = df[df[LABEL_COLUMN].isin(valid_labels)]

    df = df.dropna()

    print("\nValid rows:", len(df))
    print("\nRisk level counts:")
    print(df[LABEL_COLUMN].value_counts())

    X = df[FEATURES]
    y_text = df[LABEL_COLUMN]

    encoder = LabelEncoder()
    y = encoder.fit_transform(y_text)

    X_train, X_test, y_train, y_test = train_test_split(
        X,
        y,
        test_size=0.20,
        random_state=42,
        stratify=y
    )

    model = RandomForestClassifier(
        n_estimators=80,
        max_depth=16,
        min_samples_leaf=20,
        max_features="sqrt",
        random_state=42,
        class_weight="balanced",
        n_jobs=-1
    )

    print("\nTraining Random Forest model...")
    model.fit(X_train, y_train)

    y_pred = model.predict(X_test)

    print("\nTraining complete.")
    print("Accuracy:", accuracy_score(y_test, y_pred))

    print("\nClassification report:")
    print(classification_report(
        y_test,
        y_pred,
        target_names=encoder.classes_,
        zero_division=0
    ))

    print("\nConfusion matrix:")
    print(confusion_matrix(y_test, y_pred))

    joblib.dump(model, MODEL_PATH, compress=3)
    joblib.dump(encoder, ENCODER_PATH)

    print("\nSaved files:")
    print(MODEL_PATH)
    print(ENCODER_PATH)


if __name__ == "__main__":
    main()