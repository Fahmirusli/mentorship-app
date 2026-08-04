import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';

class MentorEarningsScreen extends StatefulWidget {
  const MentorEarningsScreen({super.key});

  @override
  State<MentorEarningsScreen> createState() => _MentorEarningsScreenState();
}

class _MentorEarningsScreenState extends State<MentorEarningsScreen> {
  bool _isLoading = true;
  double _totalEarnings = 0.0;
  List<dynamic> _transactions = [];

  @override
  void initState() {
    super.initState();
    _fetchEarnings();
  }

  Future<void> _fetchEarnings() async {
    try {
      final walletData = await ApiService.getWallet();
      if (mounted) {
        setState(() {
          if (walletData != null) {
            final balance = walletData['balance'];
            if (balance is num) {
              _totalEarnings = balance.toDouble();
            } else if (balance is String) {
              _totalEarnings = double.tryParse(balance) ?? 0.0;
            } else {
              _totalEarnings = 0.0;
            }
            _transactions = walletData['transactions'] ?? walletData['withdrawals'] ?? [];
          }
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showWithdrawalDialog() {
    final _amountController = TextEditingController();
    final _bankNameController = TextEditingController();
    final _accountNumberController = TextEditingController();
    final _accountNameController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) {
        bool _isSubmitting = false;
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Request Withdrawal'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: _amountController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Amount (RM)', hintText: 'Min RM50'),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _bankNameController,
                      decoration: const InputDecoration(labelText: 'Bank Name'),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _accountNumberController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Account Number'),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _accountNameController,
                      decoration: const InputDecoration(labelText: 'Account Holder Name'),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
                ElevatedButton(
                  onPressed: _isSubmitting ? null : () async {
                    final amount = double.tryParse(_amountController.text) ?? 0;
                    if (amount < 50) {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Minimum withdrawal is RM50')));
                      return;
                    }
                    if (_bankNameController.text.isEmpty || _accountNumberController.text.isEmpty || _accountNameController.text.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please fill all fields')));
                      return;
                    }
                    
                    setDialogState(() => _isSubmitting = true);
                    final result = await ApiService.requestWithdrawal(
                      amount: amount,
                      bankName: _bankNameController.text,
                      accountNumber: _accountNumberController.text,
                      accountName: _accountNameController.text,
                    );
                    
                    if (mounted) {
                      setDialogState(() => _isSubmitting = false);
                      Navigator.pop(context);
                      if (result['success'] == true) {
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Withdrawal requested successfully!')));
                        _fetchEarnings();
                      } else {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message'] ?? 'Failed to request withdrawal')));
                      }
                    }
                  },
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2E7D32)),
                  child: _isSubmitting ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Submit', style: TextStyle(color: Colors.white)),
                ),
              ],
            );
          }
        );
      }
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F7F0),
      appBar: AppBar(
        title: const Text("My Earnings & Wallet", style: TextStyle(color: Color(0xFF2E7D32), fontWeight: FontWeight.w800, fontSize: 22)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        iconTheme: const IconThemeData(color: Color(0xFF2E7D32)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF2E7D32)))
          : RefreshIndicator(
              onRefresh: _fetchEarnings,
              color: const Color(0xFF2E7D32),
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                children: [
                  // Premium Wallet Balance Card
                  Container(
                    padding: const EdgeInsets.all(28),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF1B5E20), Color(0xFF2E7D32)], // Sleek modern green gradient
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(30),
                      boxShadow: [
                        BoxShadow(color: const Color(0xFF1B5E20).withOpacity(0.4), blurRadius: 20, offset: const Offset(0, 10))
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text("Available Balance", style: TextStyle(color: Colors.white70, fontSize: 16, fontWeight: FontWeight.w500)),
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), borderRadius: BorderRadius.circular(12)),
                              child: const Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 20),
                            )
                          ],
                        ),
                        const SizedBox(height: 15),
                        Text("RM ${_totalEarnings.toStringAsFixed(2)}", style: const TextStyle(color: Colors.white, fontSize: 40, fontWeight: FontWeight.w900, letterSpacing: -1)),
                        const SizedBox(height: 30),
                        ElevatedButton(
                          onPressed: _showWithdrawalDialog,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: const Color(0xFF1B5E20),
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            elevation: 0,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            minimumSize: const Size(double.infinity, 50),
                          ),
                          child: const Text("Withdraw Funds", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ).animate().fadeIn(duration: 500.ms, curve: Curves.easeOut).slideY(begin: 0.05),

                  const SizedBox(height: 35),

                  // Recent Transactions Header
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text("Recent Transactions", style: TextStyle(fontSize: 19, fontWeight: FontWeight.w800, color: Color(0xFF2E7D32))),
                      TextButton(
                        onPressed: () {}, // Future expansion
                        child: const Text("View All", style: TextStyle(color: Color(0xFF2E7D32), fontWeight: FontWeight.w600)),
                      )
                    ],
                  ),
                  const SizedBox(height: 10),

                  _transactions.isEmpty
                      ? Container(
                          padding: const EdgeInsets.all(40),
                          margin: const EdgeInsets.only(top: 10),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(24),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: Column(
                            children: [
                              Icon(Icons.receipt_long_rounded, size: 60, color: Colors.grey.shade300),
                              const SizedBox(height: 15),
                              const Text("No transactions yet", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                            ],
                          ),
                        ).animate().fadeIn(delay: 200.ms)
                      : Column(
                          children: _transactions.map((tx) => _transactionCard(tx)).toList(),
                        ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.1),
                  
                  const SizedBox(height: 100), // Padding for bottom nav bar
                ],
              ),
            ),
    );
  }

  Widget _transactionCard(dynamic tx) {
    double amount = 0.0;
    if (tx['amount'] is num) {
      amount = tx['amount'].toDouble();
    } else if (tx['amount'] is String) {
      amount = double.tryParse(tx['amount']) ?? 0.0;
    }
    
    final String type = tx['type'] ?? 'payment';
    final String date = tx['date'] ?? 'Unknown date';
    final String menteeName = tx['mentee_name'] ?? 'Mentee';
    
    final bool isCredit = type == 'payment' || type == 'credit';
    final Color iconColor = isCredit ? const Color(0xFF10B981) : const Color(0xFFEF4444);
    
    return InkWell(
      onTap: () {
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text("Transaction Details", style: TextStyle(color: Color(0xFF2E7D32), fontWeight: FontWeight.bold)),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text("Type: ${isCredit ? 'Payment Received' : 'Withdrawal'}", style: const TextStyle(fontSize: 16)),
                const SizedBox(height: 8),
                Text("Amount: RM ${amount.toStringAsFixed(2)}", style: const TextStyle(fontSize: 16)),
                const SizedBox(height: 8),
                Text("Date: $date", style: const TextStyle(fontSize: 16)),
                if (isCredit) ...[
                  const SizedBox(height: 8),
                  Text("From Mentee: $menteeName", style: const TextStyle(fontSize: 16)),
                ],
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text("Close", style: TextStyle(color: Color(0xFF2E7D32))),
              ),
            ],
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          ),
        );
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 16),
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.grey.shade100),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10, offset: const Offset(0, 4))],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: iconColor.withOpacity(0.1), borderRadius: BorderRadius.circular(16)),
              child: Icon(isCredit ? Icons.arrow_downward_rounded : Icons.account_balance_rounded, color: iconColor, size: 22),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(isCredit ? "Payment from $menteeName" : "Bank Withdrawal", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF2E7D32))),
                  const SizedBox(height: 4),
                  Text(date, style: const TextStyle(color: Colors.grey, fontSize: 13, fontWeight: FontWeight.w500)),
                ],
              ),
            ),
            Text(
              "${isCredit ? '+' : '-'}RM ${amount.toStringAsFixed(2)}",
              style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: isCredit ? const Color(0xFF2E7D32) : const Color(0xFFEF4444)),
            ),
          ],
        ),
      ),
    );
  }
}
