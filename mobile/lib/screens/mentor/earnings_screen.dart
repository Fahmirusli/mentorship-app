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
    final statsData = await ApiService.getMentorStats();
    if (mounted) {
      setState(() {
        if (statsData != null) {
          _totalEarnings = (statsData['total_earnings'] ?? 0).toDouble();
          _transactions = statsData['recent_transactions'] ?? [];
        }
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F7F0),
      appBar: AppBar(
        title: const Text("My Earnings & Wallet", style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black87),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF2E7D32)))
          : RefreshIndicator(
              onRefresh: _fetchEarnings,
              color: const Color(0xFF2E7D32),
              child: ListView(
                padding: const EdgeInsets.all(20),
                children: [
                  // Wallet Balance Card
                  Container(
                    padding: const EdgeInsets.all(25),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF2E7D32), Color(0xFF66BB6A)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(25),
                      boxShadow: [
                        BoxShadow(color: const Color(0xFF2E7D32).withOpacity(0.3), blurRadius: 15, offset: const Offset(0, 8))
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text("Available Balance", style: TextStyle(color: Colors.white70, fontSize: 16)),
                        const SizedBox(height: 10),
                        Text("RM ${_totalEarnings.toStringAsFixed(2)}", style: const TextStyle(color: Colors.white, fontSize: 36, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 25),
                        Row(
                          children: [
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: () {
                                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Withdrawal feature coming soon!")));
                                },
                                icon: const Icon(Icons.account_balance_wallet, color: Color(0xFF2E7D32)),
                                label: const Text("Withdraw Funds", style: TextStyle(color: Color(0xFF2E7D32), fontWeight: FontWeight.bold)),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 12),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                ),
                              ),
                            ),
                          ],
                        )
                      ],
                    ),
                  ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.1),

                  const SizedBox(height: 30),

                  // Recent Transactions
                  const Text("Recent Transactions", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 15),

                  _transactions.isEmpty
                      ? Container(
                          padding: const EdgeInsets.all(30),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.05), blurRadius: 10)],
                          ),
                          child: const Center(child: Text("No transactions yet.", style: TextStyle(color: Colors.grey))),
                        )
                      : Column(
                          children: _transactions.map((tx) => _transactionCard(tx)).toList(),
                        ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.1),
                ],
              ),
            ),
    );
  }

  Widget _transactionCard(dynamic tx) {
    final double amount = (tx['amount'] ?? 0).toDouble();
    final String type = tx['type'] ?? 'payment';
    final String date = tx['date'] ?? 'Unknown date';
    final String menteeName = tx['mentee_name'] ?? 'Mentee';
    
    final bool isCredit = type == 'payment' || type == 'credit';
    final Color txColor = isCredit ? const Color(0xFF2E7D32) : Colors.red;

    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
        boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.05), blurRadius: 10)],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: isCredit ? const Color(0xFFE8F5E9) : const Color(0xFFFFEBEE),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              isCredit ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
              color: txColor,
            ),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(isCredit ? "Payment from $menteeName" : "Withdrawal", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                Text(date, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
              ],
            ),
          ),
          Text(
            "${isCredit ? '+' : '-'}RM ${amount.toStringAsFixed(2)}",
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: txColor),
          ),
        ],
      ),
    );
  }
}
