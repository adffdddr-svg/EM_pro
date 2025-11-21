# إعداد VirtualHost لمشروع EM_pro

## الخطوات المطلوبة

### 1. تعديل ملف hosts

1. افتح ملف `hosts` كمسؤول:
   ```
   C:\Windows\System32\drivers\etc\hosts
   ```

2. أضف هذا السطر في نهاية الملف:
   ```
   127.0.0.1    empro.local
   ```

3. احفظ الملف

---

### 2. إعداد VirtualHost في Apache

1. افتح ملف `httpd-vhosts.conf`:
   ```
   C:\wamp64\bin\apache\apache2.4.xx\conf\extra\httpd-vhosts.conf
   ```
   (استبدل `apache2.4.xx` برقم إصدار Apache لديك)

2. أضف هذا الكود في نهاية الملف:

```apache
# VirtualHost للمشروع الرئيسي (localhost)
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/wamp64/www"
    
    <Directory "C:/wamp64/www">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# VirtualHost لمشروع EM_pro
<VirtualHost *:80>
    ServerName empro.local
    DocumentRoot "C:/wamp64/www/EM_pro"
    
    <Directory "C:/wamp64/www/EM_pro">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
    
    ErrorLog "logs/empro_error.log"
    CustomLog "logs/empro_access.log" common
</VirtualHost>
```

3. احفظ الملف

---

### 3. إعادة تشغيل Apache

1. انقر بزر الماوس الأيمن على أيقونة WampServer
2. اختر: **Restart All Services**
3. أو: **Apache → Service → Restart Service**

---

### 4. تعديل Cloudflare Tunnel

1. افتح Cloudflare Zero Trust Dashboard
2. اذهب إلى: **Networks → Tunnels**
3. اختر Tunnel الخاص بك
4. اضغط على **Public Hostname**
5. عدّل **Service URL** من:
   ```
   http://localhost:80
   ```
   إلى:
   ```
   http://empro.local
   ```
6. احفظ التغييرات

---

### 5. إعادة تشغيل Cloudflare Tunnel

1. افتح Command Prompt كمسؤول
2. اذهب إلى مجلد Cloudflare:
   ```
   cd C:\cloudflared
   ```
3. أوقف Tunnel الحالي (اضغط Ctrl+C إذا كان يعمل)
4. شغّل Tunnel من جديد:
   ```
   cloudflared-windows-amd64.exe tunnel run --token YOUR_TOKEN
   ```
   (استبدل `YOUR_TOKEN` بالـ Token الخاص بك)

---

## المسارات الصحيحة

### الوصول المحلي:
- ✅ `http://localhost/EM_pro` - يعمل
- ✅ `http://localhost/EM_pro/index.php` - يعمل
- ✅ `http://localhost/EM_pro/auth/login.php` - يعمل
- ✅ `http://empro.local` - يعمل بعد إعداد VirtualHost
- ✅ `http://empro.local/auth/login.php` - يعمل بعد إعداد VirtualHost

### الوصول عبر Cloudflare Tunnel:
- ✅ `https://xxxx-xxxx.trycloudflare.com` - يعمل بعد تعديل Service URL
- ✅ `https://xxxx-xxxx.trycloudflare.com/auth/login.php` - يعمل

---

## ملاحظات مهمة

1. **لا يوجد مجلد `public`** في المشروع
2. المسار الصحيح لتسجيل الدخول هو: `/auth/login.php` وليس `/public/login.php`
3. بعد إعداد VirtualHost، يمكنك استخدام `empro.local` مباشرة بدون `/EM_pro`
4. تأكد من حفظ ملف `hosts` كمسؤول
5. تأكد من إعادة تشغيل Apache بعد تعديل `httpd-vhosts.conf`

---

## اختبار الإعداد

1. افتح المتصفح على جهازك
2. اذهب إلى: `http://empro.local`
3. يجب أن ترى صفحة المشروع الرئيسية
4. افتح رابط Cloudflare Tunnel من هاتفك
5. يجب أن يعمل المشروع بشكل صحيح

---

## حل المشاكل

### إذا ظهرت صفحة WampServer:
- تأكد من أن VirtualHost مضبوط بشكل صحيح
- تأكد من إعادة تشغيل Apache
- تأكد من أن `DirectoryIndex` موجود في `.htaccess`

### إذا ظهر خطأ 403 Forbidden:
- تأكد من أن `Require all granted` موجود في VirtualHost
- تأكد من أن `AllowOverride All` موجود

### إذا لم يعمل empro.local:
- تأكد من حفظ ملف `hosts` كمسؤول
- تأكد من أن السطر `127.0.0.1 empro.local` موجود في ملف `hosts`
- أعد تشغيل المتصفح أو امسح الـ DNS cache:
  ```
  ipconfig /flushdns
  ```

