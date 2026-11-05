// ============================================================================
// اسم الملف: lib/models/member_profile_model.dart
// المشروع: تطبيق وصال (Wesal) - Modern Premium UI 2026
// الوصف: موديل بروفايل العضو المزود بمحرك ترجمة كامل ومطور يعتمد المطابقة الموحدة.
// ============================================================================

class AttributeTranslator {
  /// دالة تنظيف شاملة تزيل الإيموجي والمحارف غير المرئية والمسافات الزائدة
  static String cleanEmojis(String input) {
    if (input.isEmpty) return '';
    final emojiRegex = RegExp(
      r'[\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{1F000}-\u{1F02F}\u{1F0A0}-\u{1F0DF}\u{1F100}-\u{1F1FF}\u{1F200}-\u{1F2FF}\u{200D}\u{FE0F}\u{200B}-\u{200D}\u{00A0}]',
      unicode: true,
    );
    String cleaned = input.replaceAll(emojiRegex, '');
    return cleaned.replaceAll(RegExp(r'\s+'), ' ').trim();
  }

  /// فلتر صارم للتأكد من أن النص المدخل صالح وليس نص زر أو رسالة نظام ملوثة
  static bool isValidValue(String? val) {
    if (val == null) return false;
    final clean = val.trim();
    if (clean.isEmpty ||
        clean == 'null' ||
        clean == 'undefined' ||
        RegExp(r'^[-_]+$').hasMatch(clean)) {
      return false;
    }

    final lower = clean.toLowerCase();
    if (lower.contains('ارسل رسالة') ||
        lower.contains('أرسل رسالة') ||
        lower.contains('طلبات الاهتمام') ||
        lower.contains('يمكن استخدامها') ||
        lower.contains('express interest') ||
        lower.contains('send message')) {
      return false;
    }

    return true;
  }

  static final Map<String, String> _religionIdMapAr = {
    '1': 'إسلام ☪️',
    '2': 'مسيحية ✝️',
    '3': 'غير ذلك',
  };

  static final Map<String, String> _religionIdMapEn = {
    '1': 'Islam ☪️',
    '2': 'Christianity ✝️',
    '3': 'Other',
  };

  static final Map<String, String> _maritalIdMapAr = {
    '1': 'أعزب / عزباء 💍',
    '2': 'متزوج / متزوجة 💍',
    '3': 'مطلق / مطلقة 💍',
    '4': 'أرمل / أرملة 💍',
  };

  static final Map<String, String> _maritalIdMapEn = {
    '1': 'Single 💍',
    '2': 'Married 💍',
    '3': 'Divorced 💍',
    '4': 'Widowed 💍',
  };

  static final Map<String, String> _familyValueIdMapAr = {
    '1': 'عائلة محافظة',
    '2': 'عائلة محافظة جداً',
    '3': 'عائلة متدينة',
    '4': 'عائلة معتدلة / وسطية',
    '5': 'عائلة منفتحة / ليبرالية',
    '6': 'عائلة اجتماعية / بسيطة',
  };

  static final Map<String, String> _familyValueIdMapEn = {
    '1': 'Conservative Family',
    '2': 'Very Conservative Family',
    '3': 'Religious Family',
    '4': 'Moderate Family',
    '5': 'Liberal Family',
    '6': 'Social / Simple Family',
  };

  static final Map<String, String> _arToEnMap = {
    'مستقل': 'Freelancer / Independent',
    'عمل حر': 'Self-employed / Freelance',
    'طب': 'Medicine / Medical',
    'طبيب / طبيبة': 'Doctor / Medical Professional',
    'طبيب': 'Doctor',
    'طبيبة': 'Doctor',
    'هندسة': 'Engineering',
    'مهندس / مهندسة': 'Engineer',
    'مهندس': 'Engineer',
    'مهندسة': 'Engineer',
    'تعليم / تدريس': 'Education / Teaching',
    'معلم / معلمة': 'Teacher',
    'تجارة / أعمال': 'Business / Trade',
    'محاسبة / مالية': 'Accounting / Finance',
    'برمجة / تقنية': 'IT / Software Engineering',
    'موظف / يعمل': 'Employed',
    'صاحب عمل / أعمال حرة': 'Business Owner',
    'طالب / طالبة': 'Student',
    'لا يعمل حالياً': 'Unemployed',
    'إعدادية': 'Middle School',
    'ثانوية': 'High School',
    'دبلوم': 'Diploma',
    'دبلوم تقني / معهد': 'Diploma',
    'بكالوريوس': 'Bachelor Degree',
    'بكالوريوس / جامعي': 'Bachelor Degree',
    'ماجستير': 'Master Degree',
    'دكتوراه': 'PhD / Doctorate',
    'أخرى': 'Other',
    'أعزب / عزباء 💍': 'Single 💍',
    'أعزب / عزباء': 'Single',
    'أعزب': 'Single',
    'عزباء': 'Single',
    'متزوج / متزوجة 💍': 'Married 💍',
    'متزوج / متزوجة': 'Married',
    'متزوج': 'Married',
    'متزوجة': 'Married',
    'مطلق / مطلقة 💍': 'Divorced 💍',
    'مطلق / مطلقة': 'Divorced',
    'مطلق': 'Divorced',
    'مطلقة': 'Divorced',
    'أرمل / أرملة 💍': 'Widowed 💍',
    'أرمل / أرملة': 'Widowed',
    'أرمل': 'Widowed',
    'أرملة': 'Widowed',
    'بني': 'Brown',
    'بني داكن': 'Dark Brown',
    'بني فاتح': 'Light Brown',
    'أسود': 'Black',
    'أزرق': 'Blue',
    'أخضر': 'Green',
    'عسلي': 'Hazel',
    'رمادي': 'Grey',
    'رمادي / شيب': 'Grey / White',
    'أبيض / شيب': 'White / Grey',
    'أشقر': 'Blonde',
    'أحمر': 'Red',
    'أبيض': 'Fair / White',
    'بيضاء': 'Fair / Light',
    'حنطي': 'Wheat / Tan',
    'حنطي فاتح': 'Light Tan',
    'حنطية / حنطي': 'Wheatish / Medium',
    'أسمر': 'Dark Tan',
    'أسمر داكن': 'Dark',
    'سمراء / داكنة': 'Dark / Black',
    'برونزية / أسمر فاتح': 'Bronze / Olive',
    'نحيف / رشيق': 'Slim',
    'متوسط / متناسق': 'Average',
    'متوسط': 'Average',
    'ممتلئ / ضخم': 'Heavy',
    'رياضي / متناسق': 'Athletic',
    'مغترب - مقيم': 'Expat - Resident',
    'مغترب - مقيم 🏠': 'Expat - Resident 🏠',
    'مقيم في بلدي الأصلي (غير مسافر)': 'Resident in Home Country',
    'مقيم في بلدي الأصلي (غير مسافر) 🏡': 'Resident in Home Country 🏡',
    'مغترب - إقامة دائمة ✈️': 'Permanent Resident ✈️',
    'مغترب - إقامة دائمة': 'Permanent Resident',
    'مغترب - إقامة عمل 💼': 'Work Visa 💼',
    'مغترب - إقامة عمل': 'Work Visa',
    'حاصل على الجنسية 🌐': 'Citizen 🌐',
    'حاصل على الجنسية': 'Citizen',
    'سوريا': 'Syria',
    'Syria': 'Syria',
    'ملك خاص': 'Own House',
    'إيجار': 'Rented House',
    'بمفردي في مسكني الخاص 🏠': 'Living alone in my own place 🏠',
    'بمفردي في مسكني الخاص': 'Living alone in my own place',
    'مع العائلة الكريمة 👨‍👩‍👧': 'With family 👨‍👩‍👧',
    'مع العائلة الكريمة': 'With family',
    'مع العائلة': 'With family',
    'أعيش برفقة أطفالي 👶': 'Living with my children 👶',
    'أعيش برفقة أطفالي': 'Living with my children',
    'في سكن مشترك / مع أصدقاء 🤝': 'Shared housing / with friends 🤝',
    'في سكن مشترك / مع أصدقاء': 'Shared housing / with friends',
    'عصري ومتميز 🌟': 'Trendy & Distinctive 🌟',
    'عصري ومتميز': 'Trendy & Distinctive',
    'أنيق وكاجوال عصري 👟': 'Smart Casual 👟',
    'أنيق وكاجوال عصري': 'Smart Casual',
    'رسمي وكلاسيكي فاخر 👔': 'Formal & Classic 👔',
    'رسمي وكلاسيكي فاخر': 'Formal & Classic',
    'محافظ ومحتشم 🧕': 'Modest & Conservative 🧕',
    'محافظ ومحتشم': 'Modest & Conservative',
    'القراءة والاطلاع 📚': 'Reading & Knowledge 📚',
    'القراءة والاطلاع': 'Reading & Knowledge',
    'القراءة 📚': 'Reading 📚',
    'القراءة': 'Reading',
    'السفر واستكشاف أماكن جديدة ✈': 'Traveling & Exploring ✈',
    'السفر واستكشاف أماكن جديدة': 'Traveling & Exploring',
    'الرياضة والأنشطة البدنية 🏃‍♂️': 'Sports & Physical Activities 🏃‍♂️',
    'الرياضة والأنشطة البدنية': 'Sports & Physical Activities',
    'الرسم والتصميم والفنون 🎨': 'Drawing, Design & Arts 🎨',
    'الرسم والتصميم والفنون': 'Drawing, Design & Arts',
    'الطهي وإعداد الوصفات 👨‍🍳': 'Cooking & Recipes 👨‍🍳',
    'الطهي وإعداد الوصفات': 'Cooking & Recipes',
    'عالم التقنية والبرمجة 💻': 'Tech & Programming 💻',
    'عالم التقنية والبرمجة': 'Tech & Programming',
    'الموسيقى والاسترخاء 🎧': 'Music & Relaxation 🎧',
    'الموسيقى والاسترخاء': 'Music & Relaxation',
    'التطوير الذاتي والوعي 🌟': 'Self Development & Awareness 🌟',
    'التطوير الذاتي والوعي': 'Self Development & Awareness',
    'عالم الأعمال والاستثمار 📈': 'Business & Investment 📈',
    'عالم الأعمال والاستثمار': 'Business & Investment',
    'الثقافة والأدب والفلسفة 📖': 'Culture, Literature & Philosophy 📖',
    'الثقافة والأدب والفلسفة': 'Culture, Literature & Philosophy',
    'التكنولوجيا والابتكار 🤖': 'Technology & Innovation 🤖',
    'التكنولوجيا والابتكار': 'Technology & Innovation',
    'الموضة والأناقة 👔': 'Fashion & Elegance 👔',
    'الموضة والأناقة': 'Fashion & Elegance',
    'الطبيعة والتخييم 🌿': 'Nature & Camping 🌿',
    'الطبيعة والتخييم': 'Nature & Camping',
    'الموسيقى الهادئة والكلاسيكية 🎻': 'Calm & Classical Music 🎻',
    'الموسيقى الهادئة والكلاسيكية': 'Calm & Classical Music',
    'الطرب والأغاني الشرقية 🎤': 'Oriental Music 🎤',
    'الطرب والأغاني الشرقية': 'Oriental Music',
    'البوب والموسيقى العصرية 🎵': 'Pop & Modern Music 🎵',
    'البوب والموسيقى العصرية': 'Pop & Modern Music',
    'لا أستمع للموسيقى 🔇': 'Don\'t listen to music 🔇',
    'لا أستمع للموسيقى': 'Don\'t listen to music',
    'الكتب العلمية والمعرفية 🔬': 'Scientific & Knowledge Books 🔬',
    'الكتب العلمية والمعرفية': 'Scientific & Knowledge Books',
    'الكتب الدينية والروحية 🌙': 'Religious & Spiritual Books 🌙',
    'الكتب الدينية والروحية': 'Religious & Spiritual Books',
    'الروايات والقصص الأدبية 📖': 'Novels & Literature 📖',
    'الروايات والقصص الأدبية': 'Novels & Literature',
    'كتب التنمية وتطوير الذات 💡': 'Self-Development Books 💡',
    'كتب التنمية وتطوير الذات': 'Self-Development Books',
    'التاريخ والسير الذاتية 🏛️': 'History & Biographies 🏛️',
    'التاريخ والسير الذاتية': 'History & Biographies',
    'الدراما والقصص الواقعية 🎬': 'Drama & True Stories 🎬',
    'الدراما والقصص الواقعية': 'Drama & True Stories',
    'الأفلام الوثائقية والمعرفية 🎥': 'Documentaries 🎥',
    'الأفلام الوثائقية والمعرفية': 'Documentaries',
    'الأفلام الكوميدية والمرحة 🍿': 'Comedy & Fun Movies 🍿',
    'الأفلام الكوميدية والمرحة': 'Comedy & Fun Movies',
    'الأفلام والتحديات 💥': 'Action & Adventure 💥',
    'الأفلام والتحديات': 'Action & Adventure',
    'الأفلام التاريخية والملحمية ⚔️': 'Historical & Epic Movies ⚔️',
    'الأفلام التاريخية والملحمية': 'Historical & Epic Movies',
    'كرة القدم والرياضات الجماعية ⚽': 'Football & Team Sports ⚽',
    'كرة القدم والرياضات الجماعية': 'Football & Team Sports',
    'المشي والركض في الهواء الطلق 🏃': 'Walking & Outdoor Running 🏃',
    'المشي والركض في الهواء الطلق': 'Walking & Outdoor Running',
    'اللياقة وبناء الأجسام 🏋️‍♂️': 'Fitness & Bodybuilding 🏋️‍♂️',
    'اللياقة وبناء الأجسام': 'Fitness & Bodybuilding',
    'السباحة والأنشطة المائية 🏊': 'Swimming & Water Sports 🏊',
    'السباحة والأنشطة المائية': 'Swimming & Water Sports',
    'التنس والرياضات المضنيّة 🎾': 'Tennis & Racket Sports 🎾',
    'التنس والرياضات المضنيّة': 'Tennis & Racket Sports',
    'عاطفي جداً وأعبر بدفء 💖': 'Very affectionate & warm 💖',
    'عاطفي جداً وأعبر بدفء': 'Very affectionate & warm',
    'متوازن وأعبر في الأوقات المناسبة ✨': 'Balanced & express at right times ✨',
    'متوازن وأعبر في الأوقات المناسبة': 'Balanced & express at right times',
    'هادئ وحذر في إظهار المشاعر 🌿': 'Quiet & reserved with feelings 🌿',
    'هادئ وحذر في إظهار المشاعر': 'Quiet & reserved with feelings',
    'مرح جداً ومحب للضحك والبهجة 😄': 'Very cheerful & loves laughter 😄',
    'مرح جداً ومحب للضحك والبهجة': 'Very cheerful & loves laughter',
    'متوازن ومرح في الأوقات اللطيفة 😊': 'Balanced & fun in nice moments 😊',
    'متوازن ومرح في الأوقات اللطيفة': 'Balanced & fun in nice moments',
    'جاد معظم الوقت وقليل المزاح 😐': 'Serious most of the time 😐',
    'جاد معظم الوقت وقليل المزاح': 'Serious most of the time',
    'الحمل ♈': 'Aries ♈',
    'الحمل': 'Aries',
    'الثور ♉': 'Taurus ♉',
    'الثور': 'Taurus',
    'الجوزاء ♊': 'Gemini ♊',
    'الجوزاء': 'Gemini',
    'السرطان ♋': 'Cancer ♋',
    'السرطان': 'Cancer',
    'الأسد ♌': 'Leo ♌',
    'الأسد': 'Leo',
    'العذراء ♍': 'Virgo ♍',
    'العذراء': 'Virgo',
    'الميزان ♎': 'Libra ♎',
    'الميزان': 'Libra',
    'العقرب ♏': 'Scorpio ♏',
    'العقرب': 'Scorpio',
    'القوس ♐': 'Sagittarius ♐',
    'القوس': 'Sagittarius',
    'الجدي ♑': 'Capricorn ♑',
    'الجدي': 'Capricorn',
    'الدلو ♒': 'Aquarius ♒',
    'الدلو': 'Aquarius',
    'الحوت ♓': 'Pisces ♓',
    'الحوت': 'Pisces',
    'لا أدخن أبداً 🌿': 'Non-smoker 🌿',
    'لا أدخن أبداً': 'Non-smoker',
    'نعم، أدخن 🚬': 'Smoker 🚬',
    'نعم، أدخن': 'Smoker',
    'أحياناً (أرجيلة / فيب) 💨': 'Occasionally 💨',
    'أحياناً': 'Occasionally',
    'عادي': 'Standard Diet',
    'نباتي': 'Vegetarian',
    'صحي / دايت': 'Healthy / Diet',
    'ملتزم جداً': 'Very Religious',
    'ملتزم': 'Religious',
    'متوسط / معتدل': 'Moderate',
    'غير ملتزم': 'Not Practicing',
    'نعم': 'Yes',
    'لا': 'No',
  };

  static final Map<String, String> _enToArMap = {
    'single 💍': 'أعزب / عزباء 💍',
    'single': 'أعزب / عزباء',
    'married 💍': 'متزوج / متزوجة 💍',
    'married': 'متزوج / متزوجة',
    'divorced 💍': 'مطلق / مطلقة 💍',
    'divorced': 'مطلق / مطلقة',
    'widowed 💍': 'أرمل / أرملة 💍',
    'widowed': 'أرمل / أرملة',
    'middle school': 'إعدادية',
    'high school': 'ثانوية',
    'bachelor degree': 'بكالوريوس',
    'bachelor': 'بكالوريوس',
    'master degree': 'ماجستير',
    'master': 'ماجستير',
    'phd / doctorate': 'دكتوراه',
    'phd': 'دكتوراه',
    'diploma': 'دبلوم',
    'non-smoker 🌿': 'لا أدخن أبداً 🌿',
    'non-smoker': 'لا أدخن أبداً',
    'smoker 🚬': 'نعم، أدخن 🚬',
    'smoker': 'نعم، أدخن',
    'occasionally 💨': 'أحياناً (أرجيلة / فيب) 💨',
    'freelancer / independent': 'مستقل',
    'freelancer': 'مستقل',
    'medicine / medical': 'طب',
    'doctor': 'طبيب / طبيبة',
    'engineer': 'مهندس / مهندسة',
    'teacher': 'معلم / معلمة',
  };

  static String translateReligion(String input, {required bool isArabic}) {
    if (!isValidValue(input)) return '';
    final clean = input.trim();

    if (isArabic && _religionIdMapAr.containsKey(clean)) {
      return _religionIdMapAr[clean]!;
    }
    if (!isArabic && _religionIdMapEn.containsKey(clean)) {
      return _religionIdMapEn[clean]!;
    }

    final noEmoji = cleanEmojis(clean);
    if (noEmoji.contains('إسلام') ||
        noEmoji.contains('اسلام') ||
        noEmoji.toLowerCase().contains('islam')) {
      return isArabic ? 'إسلام ☪️' : 'Islam ☪️';
    }
    if (noEmoji.contains('مسيح') ||
        noEmoji.toLowerCase().contains('christian')) {
      return isArabic ? 'مسيحية ✝️' : 'Christianity ✝️';
    }

    return translate(clean, isArabic: isArabic);
  }

  static String translateMaritalStatus(String input, {required bool isArabic}) {
    if (!isValidValue(input)) return '';
    final clean = input.trim();

    if (isArabic && _maritalIdMapAr.containsKey(clean)) {
      return _maritalIdMapAr[clean]!;
    }
    if (!isArabic && _maritalIdMapEn.containsKey(clean)) {
      return _maritalIdMapEn[clean]!;
    }

    return translate(clean, isArabic: isArabic);
  }

  static String translateFamilyValue(String input, {required bool isArabic}) {
    if (!isValidValue(input)) return '';
    final clean = input.trim();

    if (isArabic && _familyValueIdMapAr.containsKey(clean)) {
      return _familyValueIdMapAr[clean]!;
    }
    if (!isArabic && _familyValueIdMapEn.containsKey(clean)) {
      return _familyValueIdMapEn[clean]!;
    }

    return translate(clean, isArabic: isArabic);
  }

  static String translate(String input, {required bool isArabic}) {
    if (!isValidValue(input)) return '';
    final cleanInput = input.trim();

    if (isArabic) {
      if (_enToArMap.containsKey(cleanInput)) {
        return _enToArMap[cleanInput]!;
      }
      final lowerKey = cleanInput.toLowerCase();
      if (_enToArMap.containsKey(lowerKey)) {
        return _enToArMap[lowerKey]!;
      }

      final normalizedInput = cleanEmojis(cleanInput).toLowerCase();
      for (final entry in _enToArMap.entries) {
        if (cleanEmojis(entry.key).toLowerCase() == normalizedInput) {
          return entry.value;
        }
      }
      return cleanInput;
    } else {
      if (_arToEnMap.containsKey(cleanInput)) {
        return _arToEnMap[cleanInput]!;
      }

      final normalizedInput = cleanEmojis(cleanInput);
      if (_arToEnMap.containsKey(normalizedInput)) {
        return _arToEnMap[normalizedInput]!;
      }

      for (final entry in _arToEnMap.entries) {
        final normalizedKey = cleanEmojis(entry.key);
        if (normalizedKey == normalizedInput && normalizedKey.isNotEmpty) {
          return entry.value;
        }
      }

      if (cleanInput.contains('-')) {
        final parts = cleanInput.split('-');
        final translatedParts = parts.map((part) {
          return translate(part.trim(), isArabic: isArabic);
        }).toList();
        return translatedParts.join(' - ');
      }

      return cleanInput;
    }
  }
}

class MemberProfileModel {
  final int userId;
  final String introduction;
  final BasicInfoModel basicInfo;
  final PhysicalAttributesModel? physicalAttributes;
  final ResidenceInfoModel? residenceInfo;
  final SpiritualBackgroundModel? spiritualBackground;
  final LifestyleModel? lifestyle;
  final AttitudeBehaviorModel? attitudeBehavior;
  final AstronomicInformationModel? astronomicInformation;
  final HobbiesModel? hobbies;
  final FamilyInfoModel? familyInfo;
  final PartnerExpectationModel? partnerExpectation;
  final EducationModel? education;
  final CareerModel? career;
  final int? profileMatchPercentage;
  final bool viewContactCheck;

  final bool isFemale;
  final bool canChat;
  final bool canExpressInterest;
  final bool isInterested;
  final int remainingInterests;
  final int sentInterestsCount;
  final int profileVisitorsCount;

  bool get isInterestSent => isInterested;

  MemberProfileModel({
    required this.userId,
    required this.introduction,
    required this.basicInfo,
    this.physicalAttributes,
    this.residenceInfo,
    this.spiritualBackground,
    this.lifestyle,
    this.attitudeBehavior,
    this.astronomicInformation,
    this.hobbies,
    this.familyInfo,
    this.partnerExpectation,
    this.education,
    this.career,
    this.profileMatchPercentage,
    required this.viewContactCheck,
    this.isFemale = false,
    this.canChat = true,
    this.canExpressInterest = true,
    this.isInterested = false,
    this.remainingInterests = 0,
    this.sentInterestsCount = 0,
    this.profileVisitorsCount = 0,
  });

  bool get isSubscribedActive {
    // This getter is exclusively about a paid subscription. Free female access
    // is a separate product policy and must not create a Premium badge.
    // The server is the source of truth for whether the current package is a
    // paid, active subscription. Names and validity dates alone are insufficient
    // because free and legacy packages may also have a future validity date.
    if (!basicInfo.isPremium) return false;

    final String planName = basicInfo.packageName.trim();
    final String validity = basicInfo.packageValidity.trim();
    if (planName.isEmpty || validity.isEmpty) return false;

    final DateTime? expiry = DateTime.tryParse(validity);
    if (expiry == null) return false;

    final DateTime today = DateTime.now();
    final DateTime todayDate = DateTime(today.year, today.month, today.day);
    final DateTime expiryDate = DateTime(expiry.year, expiry.month, expiry.day);
    return !expiryDate.isBefore(todayDate);
  }

  static String _safeString(dynamic value) {
    if (value == null) return '';
    String str = '';
    if (value is Map) {
      final val = value['name'] ??
          value['name_ar'] ??
          value['name_en'] ??
          value['title'] ??
          value['label'] ??
          value['value'];
      if (val == null) return '';
      str = val.toString().trim();
    } else {
      str = value.toString().trim();
    }

    return AttributeTranslator.isValidValue(str) ? str : '';
  }

  static String _firstValidString(
      Map<String, dynamic> json, List<String> keys) {
    for (final key in keys) {
      if (json.containsKey(key) && json[key] != null) {
        final val = _safeString(json[key]);
        if (AttributeTranslator.isValidValue(val)) {
          return val;
        }
      }
    }
    return '';
  }

  static Map<String, dynamic>? safeMapOrNull(dynamic data) {
    if (data == null) return null;

    if (data is Map) {
      final map = Map<String, dynamic>.from(data);
      if (map.isEmpty) return null;

      // Laravel JsonResource / ResourceCollection values can be nested as
      // {data: {...}} or {data: [...], meta: ..., links: ...}. Unwrap only
      // recognized response envelopes so model fields are read from the row.
      final nestedData = map['data'];
      final isResourceEnvelope = map.containsKey('result') ||
          map.containsKey('meta') ||
          map.containsKey('links') ||
          map.length == 1;
      if (isResourceEnvelope && (nestedData is Map || nestedData is List)) {
        return safeMapOrNull(nestedData);
      }

      return map;
    }

    if (data is List) {
      for (final item in data) {
        final record = safeMapOrNull(item);
        if (record != null) return record;
      }
    }

    return null;
  }

  factory MemberProfileModel.fromJson(Map<String, dynamic> json, [int id = 0]) {
    String intro = '';
    final dynamic rawIntroduction =
        json['intoduction'] ?? json['introduction'];
    final introMap = safeMapOrNull(rawIntroduction);
    if (introMap != null) {
      intro = _safeString(
        introMap['introduction'] ?? introMap['about'],
      );
    } else if (rawIntroduction is String) {
      intro = _safeString(rawIntroduction);
    }

    int? matchPct;
    if (json['profile_match_percentage'] != null) {
      matchPct = int.tryParse(json['profile_match_percentage'].toString());
    } else if (json['profile_match'] != null) {
      matchPct = int.tryParse(json['profile_match'].toString());
    }

    final int parsedUserId = json['user_id'] != null
        ? (int.tryParse(json['user_id'].toString()) ?? id)
        : (json['id'] != null
            ? (int.tryParse(json['id'].toString()) ?? id)
            : id);

    final eduMap = safeMapOrNull(json['education']);
    EducationModel? parsedEdu =
        eduMap != null ? EducationModel.fromJson(eduMap) : null;

    final careerMap = safeMapOrNull(json['career']);
    CareerModel? parsedCareer =
        careerMap != null ? CareerModel.fromJson(careerMap) : null;

    final basicMap = safeMapOrNull(json['basic_info']) ?? {};
    final spirMap = safeMapOrNull(json['spiritual_backgrounds']) ??
        safeMapOrNull(json['spiritual_background']);

    if ((basicMap['religion'] == null ||
            !AttributeTranslator.isValidValue(
                basicMap['religion'].toString())) &&
        spirMap != null) {
      basicMap['religion'] = spirMap['religion'] ?? spirMap['religion_id'];
    }

    final parsedBasicInfo = BasicInfoModel.fromJson(basicMap);

    // 🟢 معالجة وتحديد جنس الحساب بدقة لمنع الخطأ
    bool parsedIsFemale = json['is_female'] == true ||
        json['is_female'] == 1 ||
        json['is_female'] == '1' ||
        basicMap['is_female'] == true ||
        basicMap['is_female'] == 1 ||
        basicMap['is_female'] == '1';

    final String rawGenderStr = (basicMap['gender'] ?? json['gender'] ?? '')
        .toString()
        .trim()
        .toLowerCase();
    if (rawGenderStr == '2' ||
        rawGenderStr.contains('female') ||
        rawGenderStr.contains('أنثى') ||
        rawGenderStr.contains('انثى')) {
      parsedIsFemale = true;
    }

    bool parsedCanChat = json['can_chat'] == true ||
        json['can_chat'] == 1 ||
        json['can_chat'] == '1' ||
        basicMap['can_chat'] == true ||
        basicMap['can_chat'] == 1 ||
        basicMap['can_chat'] == '1';

    if (json['can_chat'] == null && basicMap['can_chat'] == null) {
      parsedCanChat = true;
    }

    bool parsedCanExpressInterest = json['can_express_interest'] == true ||
        json['can_express_interest'] == 1 ||
        json['can_express_interest'] == '1' ||
        basicMap['can_express_interest'] == true ||
        basicMap['can_express_interest'] == 1 ||
        basicMap['can_express_interest'] == '1';

    if (json['can_express_interest'] == null &&
        basicMap['can_express_interest'] == null) {
      parsedCanExpressInterest = true;
    }

    bool parsedIsInterested = json['is_interested'] == true ||
        json['is_interested'] == 1 ||
        json['is_interested'] == '1' ||
        basicMap['is_interested'] == true ||
        basicMap['is_interested'] == 1 ||
        basicMap['is_interested'] == '1' ||
        !parsedCanExpressInterest;

    int parsedRemainingInterests = int.tryParse(
            json['remaining_interests']?.toString() ??
                basicMap['remaining_interests']?.toString() ??
                json['remaining_interest']?.toString() ??
                '0') ??
        0;

    int parseNonNegativeCount(dynamic value) {
      final parsed = int.tryParse(value?.toString() ?? '');
      return parsed != null && parsed >= 0 ? parsed : 0;
    }

    final sentInterestsCount = parseNonNegativeCount(
      json['sent_interests_count'] ?? basicMap['sent_interests_count'],
    );
    final profileVisitorsCount = parseNonNegativeCount(
      json['profile_visitors_count'] ?? basicMap['profile_visitors_count'],
    );

    final physMap = safeMapOrNull(json['physical_attributes']);
    final resMap = safeMapOrNull(json['residence_info']) ??
        safeMapOrNull(json['residency_information']) ??
        safeMapOrNull(json['residency_info']) ??
        safeMapOrNull(json['present_address']) ??
        safeMapOrNull(json['permanent_address']);
    final lifeMap =
        safeMapOrNull(json['lifestyles']) ?? safeMapOrNull(json['lifestyle']);
    final attitudeMap = safeMapOrNull(json['attitude_behavior']);
    final astroMap = safeMapOrNull(json['astrologies']) ?? safeMapOrNull(json['astronomic_information']);
    final hobMap = safeMapOrNull(json['hobbies_interest']) ??
        safeMapOrNull(json['hobbies']);
    final famMap = safeMapOrNull(json['families_information']) ??
        safeMapOrNull(json['family_info']);
    final partnerMap = safeMapOrNull(json['partner_expectation']);

    return MemberProfileModel(
      userId: parsedUserId,
      introduction: intro,
      basicInfo: parsedBasicInfo,
      physicalAttributes:
          physMap != null ? PhysicalAttributesModel.fromJson(physMap) : null,
      residenceInfo:
          resMap != null ? ResidenceInfoModel.fromJson(resMap) : null,
      spiritualBackground:
          spirMap != null ? SpiritualBackgroundModel.fromJson(spirMap) : null,
      lifestyle: lifeMap != null ? LifestyleModel.fromJson(lifeMap) : null,
      attitudeBehavior: attitudeMap != null ? AttitudeBehaviorModel.fromJson(attitudeMap) : null,
      astronomicInformation: astroMap != null ? AstronomicInformationModel.fromJson(astroMap) : null,
      hobbies: hobMap != null ? HobbiesModel.fromJson(hobMap) : null,
      familyInfo: famMap != null ? FamilyInfoModel.fromJson(famMap) : null,
      partnerExpectation: partnerMap != null
          ? PartnerExpectationModel.fromJson(partnerMap)
          : null,
      education: parsedEdu,
      career: parsedCareer,
      profileMatchPercentage: matchPct,
      viewContactCheck: json['view_contact_check'] == true ||
          json['view_contact_check'] == 1 ||
          json['view_contact_check'] == '1',
      isFemale: parsedIsFemale,
      canChat: parsedCanChat,
      canExpressInterest: parsedCanExpressInterest,
      isInterested: parsedIsInterested,
      remainingInterests: parsedRemainingInterests,
      sentInterestsCount: sentInterestsCount,
      profileVisitorsCount: profileVisitorsCount,
    );
  }
}

class BasicInfoModel {
  final String firstName;
  final String lastName;
  final String code;
  final int age;
  final String religion;
  final String caste;
  final String dateOfBirth;
  final String noOfChildren;
  final String gender;
  final String phone;
  final String maritalStatus;
  final String onBehalfId;
  final String photo;
  final String packageValidity;
  final String packageName;
  final bool isPremium;
  final int remainingProfileViewers;

  BasicInfoModel({
    required this.firstName,
    required this.lastName,
    required this.code,
    required this.age,
    required this.religion,
    required this.caste,
    required this.dateOfBirth,
    required this.noOfChildren,
    required this.gender,
    required this.phone,
    required this.maritalStatus,
    this.onBehalfId = '',
    required this.photo,
    required this.packageValidity,
    required this.packageName,
    this.isPremium = false,
    required this.remainingProfileViewers,
  });

  String get fullName => '$firstName $lastName'.trim();

  bool get isFemale {
    final g = gender.trim().toLowerCase();
    return g == '2' ||
        g.contains('female') ||
        g.contains('أنثى') ||
        g.contains('انثى');
  }

  String getGenderText({bool isArabic = true}) {
    return isFemale
        ? (isArabic ? 'أنثى' : 'Female')
        : (isArabic ? 'ذكر' : 'Male');
  }

  String getLocalizedMaritalStatus({bool isArabic = true}) {
    return AttributeTranslator.translateMaritalStatus(maritalStatus,
        isArabic: isArabic);
  }

  String getLocalizedReligion({bool isArabic = true}) {
    return AttributeTranslator.translateReligion(religion, isArabic: isArabic);
  }

  /// Returns an explicit disclosure only for profiles created on behalf of
  /// a child or sibling. Personal accounts and unknown legacy values remain
  /// undisclosed.
  String? getManagedAccountLabel({bool isArabic = true}) {
    switch (onBehalfId.trim()) {
      case '2':
        if (isArabic) {
          return isFemale
              ? 'حساب يُدار نيابةً عن ابنتي'
              : 'حساب يُدار نيابةً عن ابني';
        }
        return isFemale
            ? 'Managed on behalf of my daughter'
            : 'Managed on behalf of my son';
      case '3':
        if (isArabic) {
          return isFemale
              ? 'حساب يُدار نيابةً عن أختي'
              : 'حساب يُدار نيابةً عن أخي';
        }
        return isFemale
            ? 'Managed on behalf of my sister'
            : 'Managed on behalf of my brother';
      default:
        return null;
    }
  }

  String getLocalizedCaste({bool isArabic = true}) {
    return AttributeTranslator.translate(caste, isArabic: isArabic);
  }

  factory BasicInfoModel.fromJson(Map<String, dynamic> json) {
    final fName =
        MemberProfileModel._firstValidString(json, ['firs_name', 'first_name']);
    final lName = MemberProfileModel._firstValidString(json, ['last_name']);

    final rawChildren = json['no_of_children'];
    String parsedChildren = '';
    if (rawChildren != null) {
      final strVal = rawChildren.toString().trim();
      if (AttributeTranslator.isValidValue(strVal)) {
        parsedChildren = strVal;
      }
    }

    final parsedReligion = MemberProfileModel._firstValidString(json,
        ['religion', 'religion_id', 'member_religion_id', 'religion_name']);

    final parsedMarital = MemberProfileModel._firstValidString(json, [
      'marital_status_id',
      'maritial_status_id',
      'member_marital_status_id',
      'maritial_status',
      'marital_status'
    ]);

    final parsedCaste = MemberProfileModel._firstValidString(
        json, ['caste', 'caste_id', 'caste_name']);

    final rawOnBehalf = json['on_behalf_id'] ??
        json['on_behalves_id'] ??
        json['onbehalf_id'] ??
        json['onbehalf'] ??
        json['on_behalf'];
    final resolvedOnBehalf = rawOnBehalf is Map
        ? (rawOnBehalf['id'] ?? rawOnBehalf['value'])
        : rawOnBehalf;
    final parsedOnBehalfId =
        MemberProfileModel._safeString(resolvedOnBehalf);

    final parsedPhoto = MemberProfileModel._firstValidString(
        json, ['photo', 'avatar', 'image']);

    final parsedValidity = MemberProfileModel._firstValidString(
        json, ['package_validity', 'validity']);

    final parsedPackage = MemberProfileModel._firstValidString(
        json, ['package_name', 'current_package_name', 'package']);
    final dynamic rawPremium = json['is_premium'];
    final bool parsedIsPremium = rawPremium == true ||
        rawPremium == 1 ||
        rawPremium?.toString().trim().toLowerCase() == 'true' ||
        rawPremium?.toString().trim() == '1';

    // 🟢 استخراج رقم الجنس كـ 2 للأنثى أو 1 للذكر
    String rawGender = MemberProfileModel._safeString(json['gender']);
    String finalGender = '1';
    final String lowerG = rawGender.toLowerCase();
    if (rawGender == '2' ||
        lowerG.contains('female') ||
        lowerG.contains('أنثى') ||
        lowerG.contains('انثى')) {
      finalGender = '2';
    } else if (json['is_female'] == true ||
        json['is_female'] == 1 ||
        json['is_female'] == '1') {
      finalGender = '2';
    }

    return BasicInfoModel(
      firstName: fName,
      lastName: lName,
      code: MemberProfileModel._safeString(json['code']),
      age: int.tryParse(json['age']?.toString() ?? '0') ?? 0,
      religion: parsedReligion,
      caste: parsedCaste,
      dateOfBirth: MemberProfileModel._firstValidString(json, ['date_of_birth', 'birthday']),
      noOfChildren: parsedChildren,
      gender: finalGender,
      phone: MemberProfileModel._safeString(json['phone']),
      maritalStatus: parsedMarital,
      onBehalfId: parsedOnBehalfId,
      photo: parsedPhoto,
      packageValidity: parsedValidity,
      packageName: parsedPackage,
      isPremium: parsedIsPremium,
      remainingProfileViewers: int.tryParse(
              json['remaining_profile_viewer_view']?.toString() ??
                  json['remaining_profile_viewers']?.toString() ??
                  '0') ??
          0,
    );
  }
}

class PhysicalAttributesModel {
  final String height;
  final String weight;
  final String eyeColor;
  final String hairColor;
  final String complexion;
  final String bloodGroup;
  final String bodyType;
  final String bodyArt;

  PhysicalAttributesModel({
    required this.height,
    required this.weight,
    required this.eyeColor,
    required this.hairColor,
    required this.complexion,
    required this.bloodGroup,
    required this.bodyType,
    required this.bodyArt,
  });

  String getLocalizedEyeColor({bool isArabic = true}) =>
      AttributeTranslator.translate(eyeColor, isArabic: isArabic);

  String getLocalizedHairColor({bool isArabic = true}) =>
      AttributeTranslator.translate(hairColor, isArabic: isArabic);

  String getLocalizedComplexion({bool isArabic = true}) =>
      AttributeTranslator.translate(complexion, isArabic: isArabic);

  String getLocalizedBodyType({bool isArabic = true}) =>
      AttributeTranslator.translate(bodyType, isArabic: isArabic);

  String getLocalizedBodyArt({bool isArabic = true}) =>
      AttributeTranslator.translate(bodyArt, isArabic: isArabic);

  factory PhysicalAttributesModel.fromJson(Map<String, dynamic> json) {
    return PhysicalAttributesModel(
      height: MemberProfileModel._safeString(json['height']),
      weight: MemberProfileModel._safeString(json['weight']),
      eyeColor: MemberProfileModel._safeString(json['eye_color']),
      hairColor: MemberProfileModel._safeString(json['hair_color']),
      complexion: MemberProfileModel._safeString(json['complexion']),
      bloodGroup: MemberProfileModel._safeString(json['blood_group']),
      bodyType: MemberProfileModel._safeString(json['raw_body_type'] ?? json['body_type']),
      bodyArt: MemberProfileModel._safeString(json['raw_body_art'] ?? json['body_art']),
    );
  }
}

class ResidenceInfoModel {
  final String birthCountry;
  final String residencyCountry;
  final String residencyState;
  final String residencyCity;
  final String growUpCountry;
  final String immigrationStatus;

  ResidenceInfoModel({
    required this.birthCountry,
    required this.residencyCountry,
    required this.residencyState,
    required this.residencyCity,
    required this.growUpCountry,
    required this.immigrationStatus,
  });

  String getLocalizedBirthCountry({bool isArabic = true}) =>
      AttributeTranslator.translate(birthCountry, isArabic: isArabic);

  String getLocalizedResidencyCountry({bool isArabic = true}) =>
      AttributeTranslator.translate(residencyCountry, isArabic: isArabic);

  String getLocalizedResidencyState({bool isArabic = true}) =>
      AttributeTranslator.translate(residencyState, isArabic: isArabic);

  String getLocalizedResidencyCity({bool isArabic = true}) =>
      AttributeTranslator.translate(residencyCity, isArabic: isArabic);

  String getLocalizedGrowUpCountry({bool isArabic = true}) =>
      AttributeTranslator.translate(growUpCountry, isArabic: isArabic);

  String getLocalizedImmigrationStatus({bool isArabic = true}) =>
      AttributeTranslator.translate(immigrationStatus, isArabic: isArabic);

  factory ResidenceInfoModel.fromJson(Map<String, dynamic> json) {
    return ResidenceInfoModel(
      birthCountry: MemberProfileModel._firstValidString(
          json, ['birth_country', 'birth_country_id', 'birth_country_name']),
      residencyCountry: MemberProfileModel._firstValidString(json, [
        'residency_country',
        'recidency_country',
        'recidency_country_id',
        'residency_country_id',
        'country'
      ]),
      residencyState: MemberProfileModel._firstValidString(json, [
        'residency_state',
        'recidency_state',
        'state_name',
        'state',
        'recidency_state_id',
        'residency_state_id'
      ]),
      residencyCity: MemberProfileModel._firstValidString(json, [
        'residency_city',
        'recidency_city',
        'city_name',
        'city',
        'recidency_city_id',
        'residency_city_id'
      ]),
      growUpCountry: MemberProfileModel._firstValidString(
          json, ['growup_country', 'growup_country_id', 'growup_country_name']),
      immigrationStatus: MemberProfileModel._firstValidString(
          json, ['immigration_status', 'immigration_status_id']),
    );
  }
}

class SpiritualBackgroundModel {
  final String religion;
  final String caste;
  final String subCaste;
  final String ethnicity;
  final String personalValue;
  final String familyValue;

  SpiritualBackgroundModel({
    required this.religion,
    required this.caste,
    required this.subCaste,
    required this.ethnicity,
    required this.personalValue,
    required this.familyValue,
  });

  String getLocalizedReligion({bool isArabic = true}) =>
      AttributeTranslator.translateReligion(religion, isArabic: isArabic);

  String getLocalizedCaste({bool isArabic = true}) =>
      AttributeTranslator.translate(caste, isArabic: isArabic);

  String getLocalizedFamilyValue({bool isArabic = true}) =>
      AttributeTranslator.translateFamilyValue(familyValue, isArabic: isArabic);

  String getLocalizedEthnicity({bool isArabic = true}) =>
      AttributeTranslator.translate(ethnicity, isArabic: isArabic);

  factory SpiritualBackgroundModel.fromJson(Map<String, dynamic> json) {
    return SpiritualBackgroundModel(
      religion: MemberProfileModel._firstValidString(json,
          ['religion', 'religion_id', 'member_religion_id', 'religion_name']),
      caste: MemberProfileModel._firstValidString(
          json, ['caste', 'caste_id', 'member_caste_id', 'caste_name']),
      subCaste: MemberProfileModel._firstValidString(
          json, ['sub_caste', 'sub_caste_id', 'sub_caste_name']),
      ethnicity: MemberProfileModel._safeString(json['ethnicity']),
      personalValue: MemberProfileModel._safeString(json['personal_value']),
      familyValue: MemberProfileModel._firstValidString(
          json, ['family_value', 'family_value_id', 'family_value_name']),
    );
  }
}

class LifestyleModel {
  final String diet;
  final String drink;
  final String smoke;
  final String livingWith;

  LifestyleModel({
    required this.diet,
    required this.drink,
    required this.smoke,
    required this.livingWith,
  });

  String getLocalizedDiet({bool isArabic = true}) =>
      AttributeTranslator.translate(diet, isArabic: isArabic);

  String getLocalizedDrink({bool isArabic = true}) =>
      AttributeTranslator.translate(drink, isArabic: isArabic);

  String getLocalizedSmoke({bool isArabic = true}) =>
      AttributeTranslator.translate(smoke, isArabic: isArabic);

  String getLocalizedLivingWith({bool isArabic = true}) =>
      AttributeTranslator.translate(livingWith, isArabic: isArabic);

  factory LifestyleModel.fromJson(Map<String, dynamic> json) {
    String rawDiet = MemberProfileModel._safeString(json['diet']);
    String rawSmoke = MemberProfileModel._safeString(json['smoke']);

    if (rawDiet.contains('دخن') ||
        rawDiet.contains('smoker') ||
        rawDiet.contains('smoke')) {
      if (rawSmoke.isEmpty) {
        rawSmoke = rawDiet;
      }
      rawDiet = '';
    }

    return LifestyleModel(
      diet: rawDiet,
      drink: MemberProfileModel._safeString(json['drink']),
      smoke: rawSmoke,
      livingWith: MemberProfileModel._safeString(json['living_with']),
    );
  }
}

class HobbiesModel {
  final String hobbies;
  final String interests;
  final String music;
  final String books;
  final String movies;
  final String tvShows;
  final String sports;
  final String fitnessActivities;
  final String dressStyles;

  HobbiesModel({
    required this.hobbies,
    required this.interests,
    required this.music,
    required this.books,
    required this.movies,
    required this.tvShows,
    required this.sports,
    required this.fitnessActivities,
    required this.dressStyles,
  });

  String getLocalizedHobbies({bool isArabic = true}) =>
      AttributeTranslator.translate(hobbies, isArabic: isArabic);

  String getLocalizedInterests({bool isArabic = true}) =>
      AttributeTranslator.translate(interests, isArabic: isArabic);

  String getLocalizedMusic({bool isArabic = true}) =>
      AttributeTranslator.translate(music, isArabic: isArabic);

  String getLocalizedBooks({bool isArabic = true}) =>
      AttributeTranslator.translate(books, isArabic: isArabic);

  String getLocalizedMovies({bool isArabic = true}) =>
      AttributeTranslator.translate(movies, isArabic: isArabic);

  String getLocalizedTvShows({bool isArabic = true}) =>
      AttributeTranslator.translate(tvShows, isArabic: isArabic);

  String getLocalizedSports({bool isArabic = true}) =>
      AttributeTranslator.translate(sports, isArabic: isArabic);

  String getLocalizedFitnessActivities({bool isArabic = true}) =>
      AttributeTranslator.translate(fitnessActivities, isArabic: isArabic);

  String getLocalizedDressStyles({bool isArabic = true}) =>
      AttributeTranslator.translate(dressStyles, isArabic: isArabic);

  factory HobbiesModel.fromJson(Map<String, dynamic> json) {
    return HobbiesModel(
      hobbies: MemberProfileModel._safeString(json['hobbies']),
      interests: MemberProfileModel._safeString(json['interests']),
      music: MemberProfileModel._safeString(json['music']),
      books: MemberProfileModel._safeString(json['books']),
      movies: MemberProfileModel._safeString(json['movies']),
      tvShows: MemberProfileModel._safeString(json['tv_shows']),
      sports: MemberProfileModel._safeString(json['sports']),
      fitnessActivities:
          MemberProfileModel._safeString(json['fitness_activities']),
      dressStyles: MemberProfileModel._safeString(json['dress_styles']),
    );
  }
}

class AttitudeBehaviorModel {
  final String affection;
  final String humor;

  const AttitudeBehaviorModel({this.affection = '', this.humor = ''});

  String getLocalizedAffection({bool isArabic = true}) =>
      AttributeTranslator.translate(affection, isArabic: isArabic);

  String getLocalizedHumor({bool isArabic = true}) =>
      AttributeTranslator.translate(humor, isArabic: isArabic);

  factory AttitudeBehaviorModel.fromJson(Map<String, dynamic> json) {
    return AttitudeBehaviorModel(
      affection: MemberProfileModel._safeString(json['affection']),
      humor: MemberProfileModel._safeString(json['humor']),
    );
  }
}

class AstronomicInformationModel {
  final String sunSign;

  const AstronomicInformationModel({this.sunSign = ''});

  String getLocalizedSunSign({bool isArabic = true}) =>
      AttributeTranslator.translate(sunSign, isArabic: isArabic);

  factory AstronomicInformationModel.fromJson(Map<String, dynamic> json) {
    return AstronomicInformationModel(
      sunSign: MemberProfileModel._safeString(json['sun_sign']),
    );
  }
}

class EducationModel {
  final String degree;
  final String institution;

  EducationModel({
    required this.degree,
    required this.institution,
  });

  String getLocalizedDegree({bool isArabic = true}) =>
      AttributeTranslator.translate(degree, isArabic: isArabic);

  String getLocalizedInstitution({bool isArabic = true}) =>
      AttributeTranslator.translate(institution, isArabic: isArabic);

  factory EducationModel.fromJson(Map<String, dynamic> json) {
    return EducationModel(
      degree: MemberProfileModel._safeString(json['degree']),
      institution: MemberProfileModel._safeString(json['institution']),
    );
  }
}

class CareerModel {
  final String designation;
  final String company;

  CareerModel({
    required this.designation,
    required this.company,
  });

  String getLocalizedDesignation({bool isArabic = true}) =>
      AttributeTranslator.translate(designation, isArabic: isArabic);

  String getLocalizedCompany({bool isArabic = true}) =>
      AttributeTranslator.translate(company, isArabic: isArabic);

  factory CareerModel.fromJson(Map<String, dynamic> json) {
    return CareerModel(
      designation: MemberProfileModel._safeString(json['designation']),
      company: MemberProfileModel._safeString(json['company']),
    );
  }
}

class FamilyInfoModel {
  final String father;
  final String mother;
  final String sibling;

  FamilyInfoModel({
    required this.father,
    required this.mother,
    required this.sibling,
  });

  String getLocalizedFather({bool isArabic = true}) =>
      AttributeTranslator.translate(father, isArabic: isArabic);

  String getLocalizedMother({bool isArabic = true}) =>
      AttributeTranslator.translate(mother, isArabic: isArabic);

  String getLocalizedSibling({bool isArabic = true}) =>
      AttributeTranslator.translate(sibling, isArabic: isArabic);

  factory FamilyInfoModel.fromJson(Map<String, dynamic> json) {
    return FamilyInfoModel(
      father: MemberProfileModel._safeString(json['father']),
      mother: MemberProfileModel._safeString(json['mother']),
      sibling: MemberProfileModel._safeString(json['sibling']),
    );
  }
}

class PartnerExpectationModel {
  final String general;
  final String height;
  final String minHeight;
  final String maxHeight;
  final String weight;
  final String minWeight;
  final String maxWeight;
  final String minAge;
  final String maxAge;
  final String maritalStatus;
  final String residenceCountry;
  final String religion;
  final String education;
  final String profession;
  final String language;
  final String preferredCountry;
  final String preferredState;
  final String eyeColor;
  final String hairColor;
  final String complexion;
  final String childrenAcceptable;
  final String smokingAcceptable;
  final String drinkingAcceptable;

  PartnerExpectationModel({
    this.general = '',
    this.height = '',
    this.minHeight = '',
    this.maxHeight = '',
    this.weight = '',
    this.minWeight = '',
    this.maxWeight = '',
    this.minAge = '',
    this.maxAge = '',
    this.maritalStatus = '',
    this.residenceCountry = '',
    this.religion = '',
    this.education = '',
    this.profession = '',
    this.language = '',
    this.preferredCountry = '',
    this.preferredState = '',
    this.eyeColor = '',
    this.hairColor = '',
    this.complexion = '',
    this.childrenAcceptable = '',
    this.smokingAcceptable = '',
    this.drinkingAcceptable = '',
  });

  String getLocalizedGeneral({bool isArabic = true}) =>
      AttributeTranslator.translate(general, isArabic: isArabic);

  String getLocalizedEducation({bool isArabic = true}) =>
      AttributeTranslator.translate(education, isArabic: isArabic);

  String getLocalizedProfession({bool isArabic = true}) =>
      AttributeTranslator.translate(profession, isArabic: isArabic);
  String getLocalizedReligion({bool isArabic = true}) =>
      AttributeTranslator.translateReligion(religion, isArabic: isArabic);

  String getLocalizedLanguage({bool isArabic = true}) =>
      AttributeTranslator.translate(language, isArabic: isArabic);

  String getLocalizedPreferredCountry({bool isArabic = true}) =>
      AttributeTranslator.translate(preferredCountry, isArabic: isArabic);

  String getLocalizedPreferredState({bool isArabic = true}) =>
      AttributeTranslator.translate(preferredState, isArabic: isArabic);

  String getLocalizedEyeColor({bool isArabic = true}) =>
      AttributeTranslator.translate(eyeColor, isArabic: isArabic);

  String getLocalizedHairColor({bool isArabic = true}) =>
      AttributeTranslator.translate(hairColor, isArabic: isArabic);

  String getLocalizedComplexion({bool isArabic = true}) =>
      AttributeTranslator.translate(complexion, isArabic: isArabic);

  String getLocalizedResidenceCountry({bool isArabic = true}) =>
      AttributeTranslator.translate(residenceCountry, isArabic: isArabic);

  String getLocalizedMaritalStatus({bool isArabic = true}) =>
      AttributeTranslator.translateMaritalStatus(maritalStatus,
          isArabic: isArabic);

  String getLocalizedChildrenAcceptable({bool isArabic = true}) =>
      AttributeTranslator.translate(childrenAcceptable, isArabic: isArabic);

  String getLocalizedSmokingAcceptable({bool isArabic = true}) =>
      AttributeTranslator.translate(smokingAcceptable, isArabic: isArabic);

  String getLocalizedDrinkingAcceptable({bool isArabic = true}) =>
      AttributeTranslator.translate(drinkingAcceptable, isArabic: isArabic);

  String getFormattedHeight({bool isArabic = true}) {
    final rawH = height.trim();
    final minH = minHeight.trim();
    final maxH = maxHeight.trim();

    if (rawH.contains('-')) {
      final parts = rawH.split('-');
      if (parts.length == 2) {
        final p1 = parts[0].trim();
        final p2 = parts[1].trim();
        return isArabic ? 'من $p1 لـ $p2' : 'From $p1 to $p2';
      }
    }

    if (minH.isNotEmpty && maxH.isNotEmpty && minH != maxH) {
      return isArabic ? 'من $minH لـ $maxH' : 'From $minH to $maxH';
    }

    if (rawH.isNotEmpty && AttributeTranslator.isValidValue(rawH)) return rawH;
    if (minH.isNotEmpty && AttributeTranslator.isValidValue(minH)) return minH;
    return '';
  }

  String getFormattedWeight({bool isArabic = true}) {
    final rawW = weight.trim();
    final minW = minWeight.trim();
    final maxW = maxWeight.trim();

    final String unit = isArabic ? 'كغ' : 'kg';

    if (rawW.contains('-')) {
      final parts = rawW.split('-');
      if (parts.length == 2) {
        final p1 = parts[0].trim();
        final p2 = parts[1].trim();
        return isArabic ? 'من $p1 لـ $p2 $unit' : 'From $p1 to $p2 $unit';
      }
    }

    if (minW.isNotEmpty && maxW.isNotEmpty && minW != maxW) {
      return isArabic ? 'من $minW لـ $maxW $unit' : 'From $minW to $maxW $unit';
    }

    if (rawW.isNotEmpty && AttributeTranslator.isValidValue(rawW)) {
      return (rawW.contains('كغ') || rawW.contains('kg'))
          ? rawW
          : '$rawW $unit';
    }
    if (minW.isNotEmpty && AttributeTranslator.isValidValue(minW)) {
      return '$minW $unit';
    }
    return '';
  }

  String getFormattedAge({bool isArabic = true}) {
    final minA = minAge.trim();
    final maxA = maxAge.trim();
    final String unit = isArabic ? 'سنة' : 'yrs';

    if (minA.isNotEmpty && maxA.isNotEmpty && minA != maxA) {
      return isArabic ? 'من $minA لـ $maxA $unit' : 'From $minA to $maxA $unit';
    }
    if (minA.isNotEmpty && AttributeTranslator.isValidValue(minA)) {
      return isArabic ? 'من $minA $unit' : 'From $minA $unit';
    }
    if (maxA.isNotEmpty && AttributeTranslator.isValidValue(maxA)) {
      return isArabic ? 'لـ $maxA $unit' : 'Up to $maxA $unit';
    }
    return '';
  }

  factory PartnerExpectationModel.fromJson(Map<String, dynamic> json) {
    return PartnerExpectationModel(
      general: MemberProfileModel._safeString(json['general']),
      height: MemberProfileModel._safeString(json['height']),
      minHeight: MemberProfileModel._safeString(json['min_height']),
      maxHeight: MemberProfileModel._safeString(json['max_height']),
      weight: MemberProfileModel._safeString(json['weight']),
      minWeight: MemberProfileModel._safeString(json['min_weight']),
      maxWeight: MemberProfileModel._safeString(json['max_weight']),
      minAge: MemberProfileModel._safeString(json['min_age']),
      maxAge: MemberProfileModel._safeString(json['max_age']),
      maritalStatus: MemberProfileModel._firstValidString(json,
          ['marital_status', 'partner_marital_status', 'marital_status_id']),
      residenceCountry: MemberProfileModel._firstValidString(json, [
        'residence_country',
        'residence_country_name',
        'residence_country_id'
      ]),
      religion: MemberProfileModel._firstValidString(
          json, ['religion', 'religion_name', 'partner_religion_id', 'religion_id']),
      education: MemberProfileModel._firstValidString(
          json, ['education', 'partner_education']),
      profession: MemberProfileModel._firstValidString(
          json, ['profession', 'partner_profession']),
      language: MemberProfileModel._firstValidString(
          json, ['language', 'language_name', 'language_id']),
      preferredCountry: MemberProfileModel._firstValidString(json,
          ['preferred_country_name', 'preferred_country', 'partner_country_name', 'partner_country_id']),
      preferredState: MemberProfileModel._firstValidString(json,
          ['preferred_state_name', 'preferred_state', 'partner_state_name', 'preferred_state_id', 'partner_state_id']),
      eyeColor: MemberProfileModel._safeString(json['eye_color']),
      hairColor: MemberProfileModel._safeString(json['hair_color']),
      complexion: MemberProfileModel._safeString(json['complexion']),
      childrenAcceptable:
          MemberProfileModel._safeString(json['children_acceptable']),
      smokingAcceptable:
          MemberProfileModel._safeString(json['smoking_acceptable']),
      drinkingAcceptable:
          MemberProfileModel._safeString(json['drinking_acceptable']),
    );
  }
}
