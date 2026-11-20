class AppAnnouncement {
  const AppAnnouncement({
    required this.id,
    required this.type,
    required this.title,
    required this.message,
    required this.buttonEnabled,
    required this.buttonText,
    required this.buttonUrl,
  });

  final int id;
  final String type;
  final String title;
  final String message;
  final bool buttonEnabled;
  final String buttonText;
  final String buttonUrl;

  factory AppAnnouncement.fromJson(Map<String, dynamic> json) {
    return AppAnnouncement(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      type: (json['type'] ?? 'note').toString(),
      title: (json['title'] ?? '').toString().trim(),
      message: (json['message'] ?? '').toString().trim(),
      buttonEnabled: _asBool(json['button_enabled']),
      buttonText: (json['button_text'] ?? '').toString().trim(),
      buttonUrl: (json['button_url'] ?? '').toString().trim(),
    );
  }

  bool get hasContent => title.isNotEmpty || message.isNotEmpty;

  bool get hasButton {
    if (!buttonEnabled || buttonText.isEmpty || buttonUrl.isEmpty) {
      return false;
    }

    final uri = Uri.tryParse(buttonUrl);
    return uri != null &&
        (uri.scheme == 'https' || uri.scheme == 'http') &&
        uri.host.isNotEmpty;
  }

  static bool _asBool(dynamic value) {
    if (value == true || value == 1) return true;
    final normalized = value?.toString().trim().toLowerCase();
    return normalized == '1' || normalized == 'true' || normalized == 'yes';
  }
}
