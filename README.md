# نظام إدارة الفعاليات

اسم الواجهة في `includes/header.php`: «إدارة الفعاليات». المجلد: `Event Management CSC382`. صفحة `index.php` عنوانها البرمجي: «قائمة الفعاليات».

`config.php` يعرّف `DB_NAME` بالقيمة `event_management_csc382`. ملف `db/event_management.sql` ينشئ قاعدة بالاسم نفسه ثم ينفذ `USE u741730784_EMCSC382`. التعارض موضح في القسم 21.

## 1. ما هو المشروع

تطبيق PHP وMySQL لإدارة فعاليات محلية: قائمة وبحث وفرز، تفاصيل، إنشاء وتعديل للمنظم أو المسؤول، تسجيل حضور بالاسم والبريد وعدد المقاعد، ولوحة للمسؤول تعرض التسجيلات.

الدليل السابق وصفه بأنه يعمل محلياً. هذا الوصف ما زال يطابق `config.php` (`127.0.0.1`).

## 2. لماذا يوجد هذا المشروع

اسم المجلد يتضمن `CSC382`. لا نص مقرر داخل `config.php` يشرح المادة. الهدف الظاهر من الصفحات: نشر فعاليات وتسجيل مقاعد وإدارتها من حسابات `users.role`.

## 3. من يستخدمه

`users.role`: `admin` أو `organizer` أو `attendee` (الافتراضي في SQL: `attendee`).

| الطرف | السلوك في الكود |
| --- | --- |
| زائر | `index.php` و`pages/event_view.php` و`pages/register.php` بلا `requireAuth` في رأس صفحة التسجيل |
| مسجّل دخوله | روابط «إنشاء فعالية» و«تسجيل الخروج» في `header.php` |
| organizer | `isOrganizer()` صحيح |
| admin | `isOrganizer()` صحيح أيضاً لأن الدالة تعيد true للمسؤول، و`isAdmin()` للوحة `pages/admin.php` |

البذرة تدرج مستخدماً واحداً: اسم الدخول `admin` ودور `admin`. تعليق SQL يقول كلمة المرور الافتراضية ولا تُنسخ هنا. الدليل السابق يكرر التحذير بتغييرها.

لا صفحة إنشاء حساب `attendee` في الشجرة. التسجيل في الفعالية يجمع `user_name` و`user_email` في جدول `registrations` وليس `user_id`.

## 4. ماذا يستطيع النظام أن يفعل

- عرض الفعاليات مع بحث في العنوان وفرز `start_datetime` أو `title` أو `created_at` (`index.php`).
- تفاصيل في `pages/event_view.php` مع أزرار للمنظم.
- إنشاء `pages/event_create.php` وتعديل `pages/event_edit.php` إن `isOrganizer()`.
- تسجيل مقاعد `pages/register.php`: اسم حتى 150 حرفاً، بريد، مقاعد، منع تكرار البريد لنفس الفعالية، ومقارنة بالسعة المتبقية.
- لوحة `pages/admin.php` عبر `requireAdmin()`.
- رفع صورة للفعالية إلى `uploads/events` بحد `MAX_FILE_SIZE` = 2 ميغابايت وامتدادات `jpg`, `jpeg`, `png`, `webp`.
- خروج `logout.php` يستدعي `logout()`.

`pages/events_list.php` في الدليل السابق: إعادة توجيه إلى `index.php`.

## 5. كيف يعمل النظام

1. الطلب يدخل صفحة PHP.
2. `includes/db_connect.php` يقرأ `config.php` وينشئ PDO.
3. `includes/auth.php` يدير الجلسة: `isAuthenticated` يتطلب `user_id` و`user_role`.
4. `includes/helpers.php`: `escape`, `validateEmail`, `validatePositiveInt`, `validateLength`, `generateCSRFToken`, `verifyCSRFToken`, `formatDateTime`, `isAllowedFileExtension`, `getBaseUrl`, `url`.
5. `includes/header.php` و`includes/footer.php` يلفان الصفحة.
6. `assets/js/app.js` و`assets/css/styles.css` للواجهة.
7. النماذج ترسل POST إلى نفس الصفحة مع `csrf_token`.

## 6. أمثلة واقعية

صفوف `INSERT` في `db/event_management.sql` (التاريخ كما في الملف، والعرض في الواجهة عبر `formatDateTime` بصيغة `Y-m-d H:i` لا بصيغة اليوم/الشهر العربية):

| العنوان | المكان | البداية في SQL | السعة |
| --- | --- | --- | --- |
| ورشة عمل تطوير الويب | قاعة المؤتمرات - المبنى الرئيسي | 2024-12-20 10:00:00 | 30 |
| محاضرة الذكاء الاصطناعي | المدرج الكبير | 2024-12-25 15:00:00 | 100 |
| معرض المشاريع الطلابية | صالة المعارض | 2024-12-28 09:00:00 | 200 |

`created_by` في هذه الصفوف = 1، أي المستخدم الأول بعد إدراج `admin`.

مثال تسجيل: زائر يفتح `pages/register.php?event_id=1`، يدخل اسماً وبريداً وعدد مقاعد لا يتجاوز المتبقي (`capacity` ناقص مجموع `seats`).

## 7. رحلة المستخدم

زائر:

1. `index.php` قائمة.
2. بحث `search` أو فرز `sort`.
3. `pages/event_view.php` لتفاصيل.
4. `pages/register.php?event_id=` ثم POST.
5. عند النجاح المتغير `$success` في الصفحة.

منظم أو مسؤول:

1. `login.php` بحقول اسم مستخدم وكلمة مرور.
2. `login()` تتحقق بـ `password_verify` ضد `password_hash`.
3. الجلسة: `user_id`, `user_username`, `user_role`.
4. إنشاء أو تعديل فعالية ورفع صورة.
5. المسؤول يفتح `pages/admin.php`.
6. `logout.php`.

## 8. الوحدات والأقسام

| المسار | الدور |
| --- | --- |
| `index.php` | القائمة |
| `login.php`, `logout.php` | جلسة |
| `config.php` | ثوابت القاعدة والرفع و`CSRF_SECRET` و`BASE_URL` |
| `db/event_management.sql` | مخطط وبذرة |
| `includes/db_connect.php` | PDO |
| `includes/auth.php` | هوية |
| `includes/helpers.php` | تحقق ومسارات |
| `includes/header.php`, `footer.php` | قالب |
| `pages/event_view.php` | تفاصيل |
| `pages/event_create.php`, `event_edit.php` | كتابة |
| `pages/register.php` | تسجيل حضور |
| `pages/admin.php` | لوحة |
| `pages/events_list.php` | تحويل حسب الدليل السابق |
| `assets/css/styles.css` | تنسيق |
| `assets/js/app.js` | واجهة |
| `assets/fonts/` | IBM Plex Sans Arabic حسب الدليل السابق |
| `uploads/events/` | صور؛ الدليل يقول يُنشأ عند أول رفع إن غاب |

## 9. الشركات والكيانات

لا اسم جهة في الجداول. اسم المجلد `CSC382`. قاعدة بديلة مذكورة في SQL: `u741730784_EMCSC382` (نمط اسم استضافة). لا ربط استضافة موثق في PHP؛ `DB_NAME` في `config.php` هو `event_management_csc382`.

## 10. الصلاحيات

| الدالة | الشرط |
| --- | --- |
| `isAuthenticated` | `$_SESSION['user_id']` و`user_role` |
| `isAdmin` | الدور `admin` |
| `isOrganizer` | `admin` أو `organizer` |
| `requireAuth` | وإلا تحويل إلى `login.php` عبر `url()` |
| `requireAdmin` | وإلا رفض اللوحة |
| إنشاء وتعديل | `if (!isOrganizer())` في صفحتي الإنشاء والتعديل |

دور `attendee` في ENUM. لا شاشة تسجيل دخول مخصصة له في البذرة (مستخدم واحد `admin`). تسجيل الحضور في النموذج لا يستخدم هذا الدور.

## 11. الأتمتة وسير العمل

- نموذج التسجيل يتحقق من CSRF ثم الحقول ثم السعة ثم تفرد البريد ثم `INSERT`.
- السعة: إن `capacity > 0` يُرفض الطلب عندما `remainingSeats < seats`. إن كانت السعة 0 ففرع الفحص لا يرفض النقص (الشرط داخل `if ($event['capacity'] > 0)`).
- لا إشعارات ولا حالات موافقة. لا عمود حالة على `registrations`.
- حذف فعالية: `ON DELETE CASCADE` على `registrations`. حذف مستخدم: `events.created_by` يصبح `ON DELETE SET NULL`.

## 12. التكامل بين الوحدات

```
header/footer + pages + index.php
    -> includes/auth.php
    -> includes/helpers.php
    -> includes/db_connect.php -> config.php
    -> MySQL: users, events, registrations
    -> uploads/events (صور)
assets/js/app.js و assets/css/styles.css
```

لا مجلد `api/`. العمليات على نفس صفحات PHP.

## 13. المصطلحات

| المصطلح | المعنى |
| --- | --- |
| capacity | حد المقاعد على `events` |
| seats | مقاعد هذا التسجيل |
| remainingSeats | `capacity - SUM(registrations.seats)` |
| created_by | من أنشأ الفعالية |
| CSRF | `csrf_token` في POST و`verifyCSRFToken` |
| BASE_URL | يُحسب من `DOCUMENT_ROOT` أو `SCRIPT_NAME` |

## 14. الأسئلة الشائعة

**ما متطلبات النسخة؟**  
الدليل السابق: PHP >= 7.4 وMySQL محلي. لا ملف يثبت الإصدار داخل المشروع.

**أين أغيّر كلمة قاعدة البيانات؟**  
`DB_PASS` في `config.php`. القيمة الحالية فارغة.

**لماذا تفشل اللوحة بعد الاستيراد؟**  
`USE u741730784_EMCSC382` في SQL بينما `config.php` يتصل بـ `event_management_csc382`. انظر القسم 21.

**كيف يُعرض التاريخ؟**  
`formatDateTime` ترجع `Y-m-d H:i` (مثال شكل: `2024-12-20 10:00`) وليس صيغة 12 ساعة.

## 15. المعمارية (ASCII)

```
[متصفح ar/rtl]
   index.php, login.php, pages/*
   header.php + styles.css + app.js
        | POST نماذج + CSRF
        v
   auth.php + helpers.php + db_connect.php
        |
        v
   MySQL
     users 1--* events 1--* registrations
        |
        v
   uploads/events/
```

## 16. التقنيات المستخدمة

| التقنية | المصدر |
| --- | --- |
| PHP | كل الصفحات |
| PDO | `db_connect.php` |
| MySQL utf8mb4 | `event_management.sql` (collation القاعدة `utf8mb4_general_ci`، جداول `utf8mb4`) |
| HTML `lang="ar"` `dir="rtl"` | `header.php` |
| CSS | `assets/css/styles.css` |
| JavaScript | `assets/js/app.js` |
| جلسات PHP | `auth.php` |
| `password_hash` | تعليق SQL و`password_verify` في `login()` |

الدليل السابق يذكر خط IBM Plex Sans Arabic تحت `assets/fonts/`.

## 17. هيكل المشروع

```
Event Management CSC382/
├── index.php
├── login.php
├── logout.php
├── config.php
├── db/event_management.sql
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── db_connect.php
│   ├── auth.php
│   └── helpers.php
├── pages/
│   ├── events_list.php
│   ├── event_view.php
│   ├── event_create.php
│   ├── event_edit.php
│   ├── register.php
│   └── admin.php
├── assets/css/styles.css
├── assets/js/app.js
├── assets/fonts/
├── assets/images/
├── uploads/events/
└── README.md
```

هيكل الدليل السابق ما زال مطابقاً لهذه المسارات.

## 18. واجهة المستخدم

- عنوان الموقع رابط إلى `index.php`.
- تنقل: الرئيسية، وإن وُجدت جلسة: إنشاء فعالية، ولوحة إن كان مسؤولاً، وخروج. وإلا: تسجيل الدخول.
- النماذج عربية (رسائل `register.php`: الاسم، البريد، المقاعد، رمز الأمان).
- `escape()` عند طباعة عنوان التسجيل.
- أيقونة تبويب `<link rel="icon">`: غير موجودة في `header.php` المقروء.

## 19. الخادم

`config.php`:

| الثابت | القيمة |
| --- | --- |
| DB_HOST | `127.0.0.1` |
| DB_NAME | `event_management_csc382` |
| DB_USER | `root` |
| DB_PASS | فارغة |
| UPLOAD_DIR | `BASE_PATH . '/uploads/events'` |
| MAX_FILE_SIZE | 2 * 1024 * 1024 |
| ALLOWED_EXTENSIONS | jpg, jpeg, png, webp |

`BASE_URL` يُحسب عند التشغيل. `CSRF_SECRET` ثابت في الملف؛ التعليق يقول تغييره في الإنتاج. لا تُنسخ القيمة هنا.

الدليل السابق للتشغيل:

```
php -S 127.0.0.1:8000
```

ثم `http://127.0.0.1:8000`. مسار الدليل القديم استخدم عنصراً نائباً `C:\[U Path]\Projects\...`. المسار الحالي للمجلد: `D:\VSCode\Projects\Event Management CSC382`.

## 20. مسار الطلب (real example)

`POST pages/register.php?event_id=1`

1. `event_id` من GET يُحوَّل إلى int. إن كان <= 0 فتحويل إلى `index.php`.
2. استعلام الفعالية مع `COALESCE(SUM(r.seats), 0)` تجميعاً حسب `e.id`.
3. إن غاب الصف: تحويل إلى القائمة.
4. POST: `verifyCSRFToken`. عند الفشل: «رمز الأمان غير صحيح».
5. التحقق من الاسم والبريد (`validateEmail`, `validateLength`) والمقاعد.
6. `SELECT id FROM registrations WHERE event_id = ? AND user_email = ?`. إن وُجد: «تم التسجيل مسبقاً بهذا البريد الإلكتروني لهذه الفعالية.»
7. `INSERT INTO registrations (event_id, user_name, user_email, seats) VALUES (?, ?, ?, ?)`.

قائمة: `GET index.php?search=ورشة&sort=title` يضيف `title LIKE` ويرتب بعمود من `start_datetime`, `title`, `created_at` فقط.

## 21. قاعدة البيانات (real tables)

**تعارض الاسم**

| المصدر | الاسم |
| --- | --- |
| `CREATE DATABASE` في `db/event_management.sql` | `event_management_csc382` |
| `USE` في نفس الملف بعدها | `u741730784_EMCSC382` |
| `DB_NAME` في `config.php` | `event_management_csc382` |
| الدليل السابق | `event_management_csc382` |

استنتاج من الكود: استيراد الملف كما هو قد يحاول استخدام قاعدة `u741730784_EMCSC382` بينما التطبيق يتصل بالاسم الآخر. إن لم تُنشأ القاعدتان فالاستيراد أو التشغيل يفشل حسب الأداة.

**users:** `id`, `username` فريد, `password_hash`, `role` ENUM، `created_at`.

**events:** `id`, `title`, `description`, `location`, `start_datetime`, `end_datetime`, `capacity` افتراضي 0, `image_filename`, `created_by` إلى `users` مع `ON DELETE SET NULL`, `created_at`.

**registrations:** `id`, `event_id` CASCADE, `user_name`, `user_email`, `seats` افتراضي 1, `registered_at`.

لا جدول منفصل للحضور بعد التسجيل. الحضور هنا هو صف `registrations`.

## 22. واجهات البرمجة

غير موجود كطبقة REST. الصفحات هي الواجهة:

| المسار | الوظيفة |
| --- | --- |
| `index.php` | GET قائمة |
| `login.php` | POST دخول |
| `logout.php` | خروج |
| `pages/event_view.php` | عرض |
| `pages/event_create.php` | إنشاء |
| `pages/event_edit.php` | تعديل |
| `pages/register.php` | تسجيل مقاعد |
| `pages/admin.php` | إدارة التسجيلات للمسؤول |

## 23. تسجيل الدخول والصلاحيات

`login($username, $password)` في `includes/auth.php`:

```sql
SELECT id, username, password_hash, role FROM users WHERE username = ?
```

ثم `password_verify`. النجاح يملأ الجلسة. `logout()` يستدعي `session_destroy`.

`requireAdmin()` في أول `pages/admin.php`. الإنشاء والتعديل يفحصان `isOrganizer()` لا `requireAuth()` بالاسم، والنتيجة العملية: بلا جلسة منظم أو مسؤول لا تكتمل الصفحة.

خصائص كعكة الجلسة Secure وHttpOnly: غير مضبوطة في `auth.php` (يكتفي بـ `session_start`).

## 24. الحماية

- PDO المحضّر في الدخول والتسجيل والقائمة.
- `htmlspecialchars` عبر `escape`.
- CSRF في POST التسجيل (والدليل السابق يقول في جميع النماذج). الدالتان عامتان في `helpers.php`.
- تحقق خادمي للحقول في `register.php`. الدليل السابق يذكر تحققاً من جهة المتصفح أيضاً (`app.js`).
- كلمات المرور `password_hash` في العمود `password_hash`.
- رفع: امتداد من اسم الملف عبر `isAllowedFileExtension` وحجم `MAX_FILE_SIZE`. فحص النوع من محتوى الملف (MIME): غير ظاهر في `helpers.php`.
- `CSRF_SECRET` مكتوب داخل `config.php`.
- لا حد محاولات دخول.
- لا `.htaccess` في قائمة الملفات.

## 25. الإعدادات

ملف واحد: `config.php`. لا `.env`.

ثوابت: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `BASE_PATH`, `UPLOAD_DIR`, `BASE_URL`, `MAX_FILE_SIZE`, `ALLOWED_EXTENSIONS`, `CSRF_SECRET`.

## 26. التكاملات الخارجية

غير موجود في الملفات الحالية. لا CDN ظاهر في `header.php` المقروء. الخط محلي حسب الدليل السابق.

## 27. المهام المجدولة

غير موجود في الملفات الحالية.

## 28. تخزين الملفات

- المجلد `uploads/events` عبر `UPLOAD_DIR`.
- اسم الملف في `events.image_filename`.
- الدليل السابق: أسماء فريدة تُنشأ عند الرفع، والحد 2MB، والصيغ JPG وJPEG وPNG وWEBP.
- إن غاب المجلد فالدليل يقول يُنشأ عند أول صورة. تأكيد سطر `mkdir` يحتاج صفحة الإنشاء؛ الثابت والمسار موثقان في `config.php`.

## 29. السجلات والمتابعة

لا جدول سجلات ولا مجلد `logs`. `created_at` و`registered_at` على الصفوف. أخطاء PDO في `register.php` داخل `catch (PDOException $e)` (الرسالة المعروضة للمستخدم تعتمد على بقية الملف).

## 30. التثبيت

1. PHP 7.4 أو أحدث حسب الدليل السابق، وMySQL.
2. عالج تعارض `USE` قبل الاستيراد أو أنشئ القاعدتين حسب ما ستستخدمه أداة MySQL، ثم استورد `db/event_management.sql`.
3. تأكد أن `DB_NAME` يطابق القاعدة التي وُجدت فيها الجداول.
4. اترك `DB_PASS` أو اكتب كلمة MySQL المحلية.
5. تأكد من قابلية الكتابة على `uploads/events`.
6. `php -S 127.0.0.1:8000` من مجلد المشروع.
7. ادخل بحساب البذرة `admin` بعد قراءة كلمة المرور من تعليق SQL، ثم غيّرها خارج بيئة العرض.

## 31. دليل التطوير

- استخدم `url()` حتى تعمل الصفحات من الجذر ومن `pages/`.
- أي نموذج حالة جديد يستدعي `generateCSRFToken` و`verifyCSRFToken`.
- الفرز في `index.php` قائمة بيضاء `$allowedSorts` حتى لا يُحقن اسم عمود.
- `formatDateTime` صيغة `Y-m-d H:i`. تغييرها قرار واجهة منفصل.
- لا تضع `CSRF_SECRET` في مستودع عام؛ القيمة الحالية ثابتة في المصدر.

## 32. النشر

غير موثق. الدليل السابق يطلب الإبقاء على المشروع داخل مجلده وتغيير كلمة المستخدم `admin` وعدم ترك اعتماد قاعدة البيانات في الإنتاج. لا ملف نشر.

## 33. النسخ الاحتياطي والاستعادة

غير موثق كسكربت. النسخ العملي: قاعدة الجداول الثلاثة ومجلد `uploads/events`. الاستعادة بإعادة SQL ثم نسخ الصور. انتبه لاسم القاعدة في `USE`.

## 34. تشخيص المشكلات

| العرض | السبب المحتمل من الملفات |
| --- | --- |
| فشل الاتصال | `config.php` أو MySQL متوقف أو اسم القاعدة لا يطابق `USE` |
| الصور لا تظهر | مجلد `uploads/events` أو `image_filename` فارغ |
| الدخول يفشل | البذرة لم تُستورد أو كلمة المرور غُيّرت |
| رمز الأمان غير صحيح | جلسة جديدة أو `CSRF_SECRET` تغيّر وسط الجلسة |
| المقاعد غير كافية | مجموع `seats` اقترب من `capacity` |
| تسجيل مكرر | نفس `user_email` لنفس `event_id` |

## 35. الاعتماديات

لا `composer.json` ولا `package.json`. PHP مع PDO_MySQL وMySQL. خط محلي حسب الدليل السابق.

## 36. القيود المعروفة

- تعارض اسم القاعدة بين `CREATE DATABASE` و`USE` و`config.php`.
- دور `attendee` بلا مسار تسجيل حساب.
- تسجيل الفعالية لا يربط `users.id`.
- `capacity = 0` لا يفعّل رفض السعة في `register.php`.
- التاريخ بصيغة 24 ساعة `Y-m-d H:i`.
- لا فحص MIME للمحتوى.
- لا CSRF على مستوى خادم موثق لغير النماذج التي تستدعي `verifyCSRFToken`؛ الدليل يقول كل النماذج.
- لا favicon في `header.php`.
- سر CSRF داخل المصدر.
- تواريخ العينة في 2024/12 (20 و25 و28) ثابتة في SQL وقد تكون ماضية حسب ساعة الخادم.

## 37. حالة النظام الحالية

الصفحات والـ SQL والإعداد موجودة كما في الدليل السابق مع تعارض `USE`. لا اختبارات ولا رقم إصدار في الملفات.

## 38. قرارات المعمارية

استنتاج من الكود: الصفحات التقليدية بلا واجهة JSON تبقي PHP مسؤولاً عن HTML والتحقق معاً. `isOrganizer()` يضم المسؤول حتى ينشئ الفعاليات بلا دور ثانٍ. تسجيل الحضور بيانات نموذج لا حساب، فيبقى التفرد على البريد لا على مستخدم.

تعارض مخطط الاستضافة: وجود `u741730784_EMCSC382` في `USE` يشير إلى نسخة نُشرت باسم قاعدة مختلف عن الثابت المحلي. المصدران كلاهما في الملفين ولا يُحسم أيهما المقصود تشغيلياً دون تعديل.

## 39. سجل التغييرات

الدليل السابق وثّق التثبيت والهيكل والأمان وحد الصور 2MB. لا ملف سجل إصدارات. تواريخ عينة الفعاليات في SQL: 2024-12-20 و2024-12-25 و2024-12-28.

## System Overview

Local PHP and MySQL event list with search, organizer editing, public seat registration, and an admin page. Database name in `config.php` is `event_management_csc382`. The SQL script also runs `USE u741730784_EMCSC382`.

## Quick Reference

| البند | القيمة |
| --- | --- |
| DB_NAME في PHP | `event_management_csc382` |
| USE في SQL | `u741730784_EMCSC382` |
| الجداول | users, events, registrations |
| المضيف | `127.0.0.1` |
| الرفع | `uploads/events` |
| حد الصورة | 2MB |
| مستخدم البذرة | `admin` |

## Quick Start

أنشئ القاعدة التي يقرأها `config.php`، صحح جملة `USE` إن لزم، ثم:

```
php -S 127.0.0.1:8000
```

من مجلد `Event Management CSC382`. افتح `http://127.0.0.1:8000`.

## For Non-Technical Users

الصفحة الرئيسية تعرض الفعاليات. افتح فعالية وسجّل اسمك وبريدك وعدد المقاعد إن بقي مكان. إنشاء الفعاليات وتعديلها بعد تسجيل دخول منظم أو مسؤول. لوحة التحكم للمسؤول فقط.

## For Developers

اقرأ `config.php` ثم `includes/auth.php` و`pages/register.php`. وحّد اسم القاعدة بين `USE` و`DB_NAME` قبل أي تشغيل. الإبقاء على قائمة الفرز البيضاء وعلى الاستعلامات المحضّرة. لا تنسخ `CSRF_SECRET`.
