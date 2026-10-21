<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'صيغة :attribute غير صحيحة.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'exists' => 'قيمة :attribute غير صحيحة.',
    'min' => [
        'numeric' => 'يجب ألا يقل :attribute عن :min.',
        'string' => 'يجب ألا يقل :attribute عن :min حروف.',
    ],
    'max' => [
        'numeric' => 'يجب ألا يزيد :attribute عن :max.',
        'string' => 'يجب ألا يزيد :attribute عن :max حرفاً.',
    ],
    'integer' => 'يجب أن يكون :attribute رقماً صحيحاً.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'password' => 'كلمة المرور',
        'login' => 'بيانات الدخول',
        'address_id' => 'عنوان التوصيل',
        'product_id' => 'المنتج',
        'quantity' => 'الكمية',
        'driver_id' => 'المندوب',
        'code' => 'كود الخصم',
    ],
];
