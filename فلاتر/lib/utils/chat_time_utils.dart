/// Shared timestamp parsing and presentation for chat previews and bubbles.
DateTime? parseChatTimestamp(String rawTime, {DateTime? now}) {
  final raw = rawTime.trim();
  if (raw.isEmpty) return null;
  final exact = DateTime.tryParse(raw);
  if (exact != null) return exact.toLocal();
  String clean = raw.toLowerCase();
  const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
  for (var i = 0; i < arabicNumbers.length; i++) {
    clean = clean.replaceAll(arabicNumbers[i], i.toString());
  }
  final reference = (now ?? DateTime.now()).toLocal();
  if (clean.contains('الآن') || clean.contains('الان') || clean.contains('now') ||
      clean.contains('لحظات') || clean.contains('just now') || clean.contains('قليل')) {
    return reference;
  }
  if (clean.contains('أمس') || clean.contains('امس') || clean.contains('yesterday')) {
    final extraDay = clean.contains('أول') || clean.contains('اول') ? 1 : 0;
    return reference.subtract(Duration(days: 1 + extraDay));
  }
  if (clean.contains('أسبوعين') || clean.contains('اسبوعين')) return reference.subtract(const Duration(days: 14));
  if (clean.contains('شهرين')) return reference.subtract(const Duration(days: 60));
  if (clean.contains('يومين')) return reference.subtract(const Duration(days: 2));
  if (clean.contains('ساعتين')) return reference.subtract(const Duration(hours: 2));
  if (clean.contains('دقيقتين')) return reference.subtract(const Duration(minutes: 2));
  final match = RegExp(r'\d+').firstMatch(clean);
  final value = match == null ? 1 : int.tryParse(match.group(0)!) ?? 1;
  if (clean.contains('ثاني') || clean.contains('ثواني') || clean.contains('second') || clean.contains('sec')) {
    return reference.subtract(Duration(seconds: value));
  }
  if (clean.contains('دقيق') || clean.contains('دقائق') || clean.contains('minute') || clean.contains('min')) {
    return reference.subtract(Duration(minutes: value));
  }
  if (clean.contains('ساع') || clean.contains('hour') || clean.contains('hr')) {
    return reference.subtract(Duration(hours: value));
  }
  if (clean.contains('يوم') || clean.contains('أيام') || clean.contains('ايام') || clean.contains('day')) {
    return reference.subtract(Duration(days: value));
  }
  if (clean.contains('أسبوع') || clean.contains('اسبوع') || clean.contains('أسابيع') || clean.contains('اسابيع') || clean.contains('week')) {
    return reference.subtract(Duration(days: value * 7));
  }
  if (clean.contains('شهر') || clean.contains('أشهر') || clean.contains('اشهر') || clean.contains('شهور') || clean.contains('month')) {
    return reference.subtract(Duration(days: value * 30));
  }
  return null;
}

String formatChatMessageTime(String rawTime, {required bool isArabic, DateTime? now}) {
  final raw = rawTime.trim();
  if (raw.isEmpty) return '';
  final parsed = DateTime.tryParse(raw)?.toLocal();
  if (parsed != null) {
    final reference = (now ?? DateTime.now()).toLocal();
    final clock = '${parsed.hour.toString().padLeft(2, '0')}:${parsed.minute.toString().padLeft(2, '0')}';
    if (parsed.year == reference.year && parsed.month == reference.month && parsed.day == reference.day) return clock;
    final yesterday = reference.subtract(const Duration(days: 1));
    if (parsed.year == yesterday.year && parsed.month == yesterday.month && parsed.day == yesterday.day) {
      return "${isArabic ? 'أمس' : 'Yesterday'} $clock";
    }
    final day = parsed.day.toString().padLeft(2, '0');
    final month = parsed.month.toString().padLeft(2, '0');
    final yearPart = parsed.year == reference.year ? '' : '/${parsed.year}';
    final date = '$day/$month$yearPart';
    return '$date $clock';
  }
  if (isArabic) return raw.replaceAll('Now', 'الآن').replaceAll('Yesterday', 'أمس');
  return raw.replaceAll('الآن', 'Now').replaceAll('الان', 'Now')
      .replaceAll('أمس', 'Yesterday').replaceAll('امس', 'Yesterday');
}
