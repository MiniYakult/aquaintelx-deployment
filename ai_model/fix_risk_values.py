import pandas as pd
import os
import shutil


BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CSV_PATH = os.path.join(BASE_DIR, "aquaintelx_dataset.csv")
BACKUP_PATH = os.path.join(BASE_DIR, "aquaintelx_dataset_backup.csv")


def normalize_risk(value):
    text = str(value).strip().lower()

    if text in ["low", "low risk", "safe"]:
        return "Low Risk"

    if text in ["moderate", "moderate risk", "medium risk", "warning"]:
        return "Moderate Risk"

    if text in ["high", "high risk", "critical", "critical risk", "unsafe", "danger"]:
        return "Critical Risk"

    raise ValueError(f"Unknown risk value found: {value}")


def main():
    if not os.path.exists(CSV_PATH):
        raise FileNotFoundError(f"CSV not found: {CSV_PATH}")

    shutil.copy(CSV_PATH, BACKUP_PATH)
    print(f"Backup created: {BACKUP_PATH}")

    df = pd.read_csv(CSV_PATH)

    if "risk_level" not in df.columns:
        raise ValueError("Column 'risk_level' not found. Do not rename it to classification.")

    df["risk_level"] = df["risk_level"].apply(normalize_risk)

    df.to_csv(CSV_PATH, index=False)

    print("CSV risk values updated successfully.")
    print(df["risk_level"].value_counts())


if __name__ == "__main__":
    main()