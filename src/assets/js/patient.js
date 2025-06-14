document.addEventListener('DOMContentLoaded', function() {
    // Load more transactions when scrolling
    const transactionsTable = document.querySelector('.transactions-table');
    if (transactionsTable) {
        let page = 1;
        let loading = false;

        window.addEventListener('scroll', function() {
            if (loading) return;

            const {scrollTop, scrollHeight, clientHeight} = document.documentElement;
            if (scrollTop + clientHeight >= scrollHeight - 5) {
                loading = true;
                page++;

                fetch(`get_transactions.php?page=${page}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.transactions && data.transactions.length > 0) {
                            const tbody = transactionsTable.querySelector('tbody');
                            data.transactions.forEach(transaction => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${new Date(transaction.transaction_date).toLocaleString()}</td>
                                    <td>KES ${parseFloat(transaction.Amount).toFixed(2)}</td>
                                    <td>${transaction.points_earned}</td>
                                `;
                                tbody.appendChild(row);
                            });
                        }
                        loading = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loading = false;
                    });
            }
        });
    }
}); 