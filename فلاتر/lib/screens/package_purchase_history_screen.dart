import 'package:flutter/material.dart';

import '../constants/colors.dart';
import '../services/api_service.dart';
import 'package_invoice_screen.dart';

class PackagePurchaseHistoryScreen extends StatefulWidget {
  final String currentLanguage;
  const PackagePurchaseHistoryScreen({super.key, this.currentLanguage = 'ar'});
  @override
  State<PackagePurchaseHistoryScreen> createState() => _PackagePurchaseHistoryScreenState();
}

class _PackagePurchaseHistoryScreenState extends State<PackagePurchaseHistoryScreen> {
  late Future<List<Map<String, dynamic>>?> _historyFuture;
  bool get _isEnglish => widget.currentLanguage == 'en';
  @override
  void initState() {
    super.initState();
    _historyFuture = ApiService.getPackagePurchaseHistory(lang: widget.currentLanguage);
  }
  Future<void> _reload() async {
    final next = ApiService.getPackagePurchaseHistory(lang: widget.currentLanguage);
    setState(() => _historyFuture = next);
    await next;
  }
  String _value(Map<String, dynamic> row, String key) {
    final value = row[key];
    return value == null || value.toString().trim().isEmpty ? '—' : value.toString();
  }
  int _paymentId(Map<String, dynamic> row) {
    final value = row['package_payment_id'] ?? row['payment_id'] ?? row['id'];
    final parsed = int.tryParse(value?.toString() ?? '');
    return parsed != null && parsed > 0 ? parsed : 0;
  }
  void _openInvoice(int paymentId) {
    if (paymentId <= 0) return;
    Navigator.of(context).push(MaterialPageRoute<void>(
      builder: (_) => PackageInvoiceScreen(paymentId: paymentId, currentLanguage: widget.currentLanguage),
    ));
  }
  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: _isEnglish ? TextDirection.ltr : TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.backgroundColor,
        appBar: AppBar(
          backgroundColor: AppColors.backgroundColor,
          foregroundColor: AppColors.textDark,
          elevation: 0,
          title: Text(_isEnglish ? 'My package invoices' : 'فواتير مشترياتي',
            style: const TextStyle(fontFamily: 'Cairo', color: AppColors.textDark, fontWeight: FontWeight.bold)),
        ),
        body: SafeArea(
          child: FutureBuilder<List<Map<String, dynamic>>?>(
            future: _historyFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
              final rows = snapshot.data;
              if (rows == null) return _message(_isEnglish ? 'Purchase history could not be loaded.' : 'تعذر تحميل سجل المشتريات.', retry: true);
              if (rows.isEmpty) return _message(_isEnglish ? 'You do not have package purchases yet.' : 'لا توجد مشتريات باقات في حسابك حالياً.', retry: false);
              return RefreshIndicator(
                color: AppColors.primaryTeal,
                onRefresh: _reload,
                child: ListView.separated(
                  padding: const EdgeInsets.all(16),
                  physics: const AlwaysScrollableScrollPhysics(),
                  itemCount: rows.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final row=rows[index];
                    final int paymentId=_paymentId(row);
                    final String status=_value(row,'payment_status');
                    final bool paid=status.toLowerCase()=='paid';
                    return Material(
                      color: AppColors.cardColor,
                      borderRadius: BorderRadius.circular(20),
                      child: InkWell(
                        borderRadius: BorderRadius.circular(20),
                        onTap: paymentId>0 ? ()=>_openInvoice(paymentId) : null,
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Row(children:[
                            Container(width:46,height:46,
                              decoration:BoxDecoration(color:AppColors.primaryTeal.withValues(alpha:0.10),borderRadius:BorderRadius.circular(14)),
                              child:const Icon(Icons.receipt_long_rounded,color:AppColors.primaryTeal)),
                            const SizedBox(width:12),
                            Expanded(child:Column(crossAxisAlignment:CrossAxisAlignment.start,children:[
                              Text(_value(row,'package_name'),maxLines:1,overflow:TextOverflow.ellipsis,
                                style:const TextStyle(fontFamily:'Cairo',color:AppColors.textDark,fontWeight:FontWeight.bold)),
                              const SizedBox(height:3),
                              Text('${_isEnglish ? 'Invoice' : 'رقم الفاتورة'}: ${_value(row,'payment_code')}',
                                style:const TextStyle(fontFamily:'Cairo',color:AppColors.textMuted,fontSize:12)),
                              Text('${_value(row,'date')} · ${_value(row,'amount')}',
                                style:const TextStyle(fontFamily:'Cairo',color:AppColors.textMuted,fontSize:11)),
                            ])),
                            const SizedBox(width:8),
                            Column(crossAxisAlignment:CrossAxisAlignment.end,children:[
                              Text(status,style:TextStyle(fontFamily:'Cairo',color:paid?AppColors.success:AppColors.warning,fontSize:11,fontWeight:FontWeight.bold)),
                              const SizedBox(height:8),
                              const Icon(Icons.chevron_left_rounded,color:AppColors.textMuted),
                            ]),
                          ]),
                        ),
                      ),
                    );
                  },
                ),
              );
            },
          ),
        ),
      ),
    );
  }
  Widget _message(String message,{required bool retry}) {
    return Center(child:Padding(padding:const EdgeInsets.all(24),child:Column(mainAxisSize:MainAxisSize.min,children:[
      const Icon(Icons.receipt_long_outlined,size:46,color:AppColors.textMuted),
      const SizedBox(height:12),
      Text(message,textAlign:TextAlign.center,style:const TextStyle(fontFamily:'Cairo',color:AppColors.textDark)),
      if(retry)...[const SizedBox(height:16),OutlinedButton(onPressed:_reload,child:Text(_isEnglish?'Retry':'إعادة المحاولة'))],
    ])));
  }
}
