# 45-Maktab — Elektron Ta'lim va Boshqaruv Tizimi

Ushbu platforma aynan **45-maktab**ning ichki o'quv jarayonlarini raqamlashtirish, o'qituvchilar va o'quvchilar o'rtasidagi aloqani ta'minlash hamda maktab ma'muriyati uchun elektron hisobotlarni shakllantirish maqsadida ishlab chiqilgan maxsus yopiq ekotizimdir.

Loyiha frontend interfeysi, 45-maktab ma'lumotlar bazasini boshqaruvchi PHP API va tahliliy hisobotlar generatsiyasi uchun Python skriptlaridan iborat.

---

## 🚀 Tizimning Asosiy Imkoniyatlari

* **45-Maktab Ma'muriyati Paneli:** Maktab ichki e'lonlarini boshqarish, o'quvchilar va o'qituvchilar tarkibini nazorat qilish.
* **Elektron Jurnal va Davomat:** O'qituvchilar tomonidan fanlar kesimida baholash va davomatni real vaqt rejimida yuritish.
* **Statistika va Tahlil:** Maktab bo'yicha umumiy reytinglar, TOP-5 eng iqtidorli o'quvchilar ro'yxati va sinflar kesimida davomat hisobotlarini olish.
* **E'lonlar paneli:** Maktab hayotiga oid muhim va shoshilinch xabarlarni (masalan, imtihonlar yoki ota-onalar yig'ilishi) foydalanuvchilarga yetkazish.

---

## 🛠️ Loyiha Tarkibi va Fayllar Strukturasi

1. **`index.html` (Frontend):** 45-maktabning rasmiy to'q ko'k (`--navy: #1a2e4a`) va oltin rangli (`--gold: #c9a84c`) brend stillarida ishlangan responsive foydalanuvchi interfeysi.
2. **`api.php` (Backend API):** 45-maktab ma'lumotlarini (foydalanuvchilar, baholar, e'lonlar) JSON formatida saqlovchi va Bearer Token orqali xavfsizlikni ta'minlovchi RESTful xizmat.
3. **`utils.py` (Tahlil va Eksport):** Maktab ichki statistikalarini hisoblash hamda ma'lumotlarni CSV formatida eksport qilish uchun terminal boshqaruv skripti.

---

## 💻 Ishga Tushirish va O'rnatish

### 1. 45-Maktab API-ni ishga tushirish:
Terminal orqali loyiha turgan papkada lokal PHP serverni yoqing:
```bash
php -S localhost:8000 -t ./
