#!/usr/bin/env python3
# ===== 45-MAKTAB PYTHON YORDAMCHI SKRIPTLAR =====
# Fayllar: python/utils.py
# Ishlatish: python3 python/utils.py

import json
import os
import sys
import csv
import hashlib
import random
from datetime import datetime, timedelta
from pathlib import Path

BASE_DIR = Path(__file__).parent.parent
DATA_DIR = BASE_DIR / "data"
DATA_DIR.mkdir(exist_ok=True)


# ════════════════════════════════════════
#  1. MA'LUMOTLAR BILAN ISHLASH
# ════════════════════════════════════════

def load_json(filename):
    """JSON fayldan ma'lumot o'qish"""
    path = DATA_DIR / f"{filename}.json"
    if not path.exists():
        return []
    with open(path, encoding="utf-8") as f:
        return json.load(f)

def save_json(filename, data):
    """JSON faylga ma'lumot yozish"""
    path = DATA_DIR / f"{filename}.json"
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  ✓  {filename}.json saqlandi ({len(data)} ta yozuv)")


# ════════════════════════════════════════
#  2. STATISTIKA HISOBOTI
# ════════════════════════════════════════

def print_statistics():
    """Maktab statistikasini chiqarish"""
    students = load_json("students")
    teachers = load_json("teachers")
    grades   = load_json("grades")

    print("\n" + "═" * 45)
    print("   45-MAKTAB — STATISTIKA HISOBOTI")
    print("═" * 45)
    print(f"  O'quvchilar soni   : {len(students)}")
    print(f"  O'qituvchilar soni : {len(teachers)}")
    print(f"  Baholar soni       : {len(grades)}")

    if grades:
        all_grades = [g["grade"] for g in grades]
        avg = sum(all_grades) / len(all_grades)
        dist = {5: 0, 4: 0, 3: 0, 2: 0}
        for g in all_grades:
            if g in dist: dist[g] += 1
        print(f"\n  O'rtacha baho     : {avg:.2f}")
        print(f"  Baho taqsimoti:")
        for mark, count in sorted(dist.items(), reverse=True):
            bar = "█" * count
            print(f"    {mark}-lar : {bar} ({count} ta)")

    if students:
        classes = {}
        for s in students:
            classes[s["cls"]] = classes.get(s["cls"], 0) + 1
        print(f"\n  Sinflar bo'yicha:")
        for cls, cnt in sorted(classes.items()):
            print(f"    {cls:6} : {cnt} o'quvchi")

    print("═" * 45 + "\n")


# ════════════════════════════════════════
#  3. BAHOLAR REYTINGI
# ════════════════════════════════════════

def top_students(n=5):
    """Eng yaxshi o'quvchilar reytingi"""
    students = load_json("students")
    grades   = load_json("grades")

    # Har bir o'quvchi uchun o'rtacha baho hisoblash
    score_map = {}
    for g in grades:
        name = g["student"]
        if name not in score_map:
            score_map[name] = []
        score_map[name].append(g["grade"])

    ranked = []
    for s in students:
        gs = score_map.get(s["name"], [s.get("avg", 0)])
        avg = round(sum(gs) / len(gs), 2) if gs else 0
        ranked.append({**s, "calc_avg": avg})

    ranked.sort(key=lambda x: x["calc_avg"], reverse=True)

    print(f"\n  🏆 TOP-{n} O'QUVCHILAR:")
    print("  " + "─" * 35)
    for i, s in enumerate(ranked[:n], 1):
        stars = "★" * int(s["calc_avg"]) + "☆" * (5 - int(s["calc_avg"]))
        print(f"  {i}. {s['name']:<22} {s['calc_avg']:.1f}  {stars}")
    print()
    return ranked[:n]


# ════════════════════════════════════════
#  4. CSV EKSPORT
# ════════════════════════════════════════

def export_students_csv(output_file=None):
    """O'quvchilarni CSV ga eksport qilish"""
    students = load_json("students")
    out_path = output_file or (BASE_DIR / "exports" / "students.csv")
    Path(out_path).parent.mkdir(exist_ok=True)

    with open(out_path, "w", newline="", encoding="utf-8-sig") as f:
        writer = csv.DictWriter(f, fieldnames=["id","name","cls","born","phone","avg"])
        writer.writeheader()
        writer.writerows(students)

    print(f"  ✓ CSV eksport: {out_path} ({len(students)} ta o'quvchi)")
    return str(out_path)

def export_grades_csv(output_file=None):
    """Baholarni CSV ga eksport qilish"""
    grades = load_json("grades")
    out_path = output_file or (BASE_DIR / "exports" / "grades.csv")
    Path(out_path).parent.mkdir(exist_ok=True)

    with open(out_path, "w", newline="", encoding="utf-8-sig") as f:
        writer = csv.DictWriter(f, fieldnames=["id","student","subject","cls","grade","date","note"])
        writer.writeheader()
        writer.writerows(grades)

    print(f"  ✓ CSV eksport: {out_path} ({len(grades)} ta baho)")
    return str(out_path)


# ════════════════════════════════════════
#  5. DEMO MA'LUMOTLAR YARATISH
# ════════════════════════════════════════

FIRST_NAMES = ["Jasur","Dilnoza","Bekzod","Malika","Sherzod","Zulfiya","Ulugbek","Sanjar","Feruza","Doniyor",
               "Sarvar","Nilufar","Bobur","Muazzam","Kamol","Shahlo","Firdavs","Ozoda","Behruz","Gulnora"]
LAST_NAMES  = ["Toshmatov","Yusupova","Nazarov","Xoliqova","Abdullayev","Raximova","Mirzayev","Qodirov",
               "Hasanova","Ergashev","Karimov","Sotvoldiyev","Umarov","Ismoilov","Xasanov"]
CLASSES     = ["7-A","7-B","8-A","8-B","9-A","9-B","10-A","11-A"]
SUBJECTS    = ["Matematika","Fizika","Kimyo","O'zbek tili","Ingliz tili","Tarix","Biologiya","Informatika"]

def generate_demo_data(n_students=30, n_grades=100):
    """Demo ma'lumotlar generatsiya qilish"""
    print(f"\n  Generatsiya boshlandi: {n_students} o'quvchi, {n_grades} baho...")

    students = []
    for i in range(1, n_students + 1):
        fn = random.choice(FIRST_NAMES)
        ln = random.choice(LAST_NAMES)
        born_year  = random.randint(2007, 2011)
        born_month = random.randint(1, 12)
        born_day   = random.randint(1, 28)
        students.append({
            "id":    i,
            "name":  f"{ln} {fn}",
            "cls":   random.choice(CLASSES),
            "born":  f"{born_year}-{born_month:02d}-{born_day:02d}",
            "phone": f"+9989{random.randint(10000000,99999999)}",
            "avg":   round(random.uniform(3.0, 5.0), 1),
        })
    save_json("students", students)

    grades = []
    for i in range(1, n_grades + 1):
        s = random.choice(students)
        days_ago  = random.randint(0, 90)
        grade_date = (datetime.now() - timedelta(days=days_ago)).strftime("%Y-%m-%d")
        grades.append({
            "id":      i,
            "student": s["name"],
            "subject": random.choice(SUBJECTS),
            "cls":     s["cls"],
            "grade":   random.choices([5,4,3,2], weights=[30,40,20,10])[0],
            "date":    grade_date,
            "note":    random.choice(["Mustaqil ish","Test","Uy vazifasi","Nazorat ishi","Ogzaki javob","Referat"]),
        })
    save_json("grades", grades)
    print(f"  ✓ Demo ma'lumotlar yaratildi!\n")


# ════════════════════════════════════════
#  6. PAROL HASHLASH (PHP bilan mos)
# ════════════════════════════════════════

def hash_password_sha256(password):
    """Parolni SHA-256 bilan hashlash (test uchun)"""
    return hashlib.sha256(password.encode()).hexdigest()

def create_user(login, password, role, name, email=""):
    """Yangi foydalanuvchi yaratish"""
    users = load_json("users")
    if any(u["login"] == login for u in users):
        print(f"  ✗ '{login}' allaqachon mavjud!")
        return None
    new_id = max([u["id"] for u in users], default=0) + 1
    # PHP password_hash bilan mos emas, lekin Python backendu uchun
    user = {"id": new_id, "login": login, "pass": hash_password_sha256(password),
            "role": role, "name": name, "email": email}
    users.append(user)
    save_json("users", users)
    print(f"  ✓ Foydalanuvchi yaratildi: {login} ({role})")
    return user


# ════════════════════════════════════════
#  7. DAVOMAT TAHLILI
# ════════════════════════════════════════

def attendance_report():
    """Davomat hisobotini tahlil qilish"""
    attendance = load_json("attendance")
    if not attendance:
        print("  ℹ  Davomat ma'lumotlari topilmadi")
        return

    total = len(attendance)
    present = sum(1 for a in attendance if a.get("status") == "p")
    absent  = sum(1 for a in attendance if a.get("status") == "a")
    late    = sum(1 for a in attendance if a.get("status") == "l")

    print(f"\n  📋 DAVOMAT HISOBOTI:")
    print(f"  Jami yozuv : {total}")
    print(f"  Keldi      : {present} ({100*present//total if total else 0}%)")
    print(f"  Kelmadi    : {absent}  ({100*absent//total  if total else 0}%)")
    print(f"  Sababli    : {late}   ({100*late//total    if total else 0}%)")


# ════════════════════════════════════════
#  ASOSIY MENYU
# ════════════════════════════════════════

def main():
    print("\n╔══════════════════════════════════════╗")
    print("║   45-MAKTAB — PYTHON YORDAMCHI TOOL  ║")
    print("╚══════════════════════════════════════╝")
    print("  1. Statistika ko'rish")
    print("  2. TOP-5 o'quvchilar")
    print("  3. O'quvchilarni CSV eksport")
    print("  4. Baholarni CSV eksport")
    print("  5. Demo ma'lumotlar yaratish")
    print("  6. Davomat hisoboti")
    print("  0. Chiqish\n")

    while True:
        choice = input("  Tanlov: ").strip()
        if choice == "1":
            print_statistics()
        elif choice == "2":
            top_students()
        elif choice == "3":
            export_students_csv()
        elif choice == "4":
            export_grades_csv()
        elif choice == "5":
            n = input("  O'quvchilar soni (default 30): ").strip()
            g = input("  Baholar soni (default 100): ").strip()
            generate_demo_data(int(n) if n.isdigit() else 30, int(g) if g.isdigit() else 100)
        elif choice == "6":
            attendance_report()
        elif choice == "0":
            print("  Xayr!\n")
            break
        else:
            print("  Noto'g'ri tanlov, qayta urinib ko'ring.")

if __name__ == "__main__":
    # Agar argument bilan chaqirilsa
    if len(sys.argv) > 1:
        cmd = sys.argv[1]
        if cmd == "stats":   print_statistics()
        elif cmd == "top":   top_students()
        elif cmd == "csv":   export_students_csv(); export_grades_csv()
        elif cmd == "demo":  generate_demo_data()
        else:
            print(f"Noma'lum buyruq: {cmd}")
            print("Buyruqlar: stats | top | csv | demo")
    else:
        main()
