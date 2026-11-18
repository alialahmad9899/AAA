import 'package:flutter/material.dart';

/// Shared rendering of the original transparent Wesal wordmark.
///
/// The source image stays unchanged. Each screen controls only the rendered
/// size so the Arabic and English lettering always keeps its proportions.
class WesalLogo extends StatelessWidget {
  final double width;
  final double height;
  final BoxFit fit;
  final bool excludeSemantics;

  const WesalLogo({
    super.key,
    this.width = 160,
    this.height = 160,
    this.fit = BoxFit.contain,
    this.excludeSemantics = false,
  });

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      'assets/images/wesal_logo.png',
      width: width,
      height: height,
      fit: fit,
      filterQuality: FilterQuality.high,
      semanticLabel: excludeSemantics ? null : 'شعار وصال',
      excludeFromSemantics: excludeSemantics,
    );
  }
}
