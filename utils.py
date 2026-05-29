#!/usr/bin/env python3
# ===== 45-MAKTAB PYTHON YORDAMCHI SKRIPTLAR =====

import json
import os
import sys
import csv
import hashlib
from pathlib import Path

BASE_DIR = Path(__file__).parent
DATA_DIR = BASE_DIR / "data"
DATA_DIR.mkdir(exist_ok=True)

def load_json(filename):
    path = DATA_DIR / f"{filename}.json"
    if not path.exists():
        return []
    with open(path, encoding="utf-8") as f:
        return json.load(f)

def save_json(filename, data):
    path = DATA_DIR / f"{filename}.json"
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

# ════════════════════════════════════════
#  TIZIMNI TOZALASH (RESET TO E-MAKTAB)
# ════════════════════════════════════════

def reset_to_emaktab():
    """Barcha namuna ma'lumotlarni o'chirib, tizimni toza holatga keltiradi"""
    confirm = input("  ⚠ Diqqat! Barcha ma'lumotlar o'chib ketadi. Rozimisiz? (ha/yo'q): ").strip().lower()
    if confirm != 'ha':
        print("  Xizmat bekor qilindi.")
        return

    # Faqat toza boshlang'ich admin qoladi
    default_admin = [
        {
            "id": 1,
            "username": "admin",
            "password": hashlib.sha256("admin123".encode()).hexdigest(),
            "role": "Admin",
            "name": "Tizim Administratori",
            "token": "admin_session_token_45"
        }
    ]
    
    save_json("users", default_admin)
    save_json("students", [])
    save_json("teachers", [])
    save_json("grades", [])
    save_json("announcements", [])
    print("\n  ✓ Tizim muvaffaqiyatli tozalandi! e-maktab.uz kabi bo'm-bo'sh holatga keltirildi.")
    print("  ℹ Standart kirish: Login: admin | Parol: admin123\n")

def print_statistics():
    students = load_json("students")
    teachers = load_json("teachers")
    grades = load_json("grades")
    print("\n  ============= 45-MAKTAB STATISTIKASI =============")
    print(f"   O'quvchilar soni : {len(students)} ta")
    print(f"   O'qituvchilar soni: {len(teachers)} ta")
    print(f"   Joriy baholar soni: {len(grades)} ta")
    print("  =================================================\n")

def export_students_csv():
    students = load_json("students")
    if not students:
        print("  ⚠ Eksport qilish uchun o'quvchilar mavjud emas.")
        return
    path = BASE_DIR / "o_quvchilar_ro_yxati.csv"
    with open(path, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["ID", "Ism, Familiya", "Sinf"])
        for s in students:
            writer.writerow([s.get("id"), s.get("name"), s.get("class") or s.get("cls")])
    print(f"  ✓ O'quvchilar '{path.name}' fayliga eksport qilindi.")

def main():
    print("\n  === 45-MAKTAB TIZIM BOSHQARUV PANELI ===")
    print("  1. Statistika ko'rish")
    print("  2. O'quvchilarni CSV eksport qilish")
    print("  3. TIZIMNI TOZALASH (e-maktab holatiga keltirish)")
    print("  0. Chiqish\n")

    while True:
        choice = input("  Tanlov: ").strip()
        if choice == "1":
            print_statistics()
        elif choice == "2":
            export_students_csv()
        elif choice == "3":
            reset_to_emaktab()
        elif choice == "0":
            print("  Xayr!\n")
            break
        else:
            print("  Noto'g'ri tanlov, qayta urinib ko'ring.")

if __name__ == "__main__":
    main()
