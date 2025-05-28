document.addEventListener('DOMContentLoaded', function () {
    const searchBox = document.getElementById('search-box');
    const resultsDiv = document.getElementById('search-results');
    let debounceTimeout;

    searchBox.addEventListener('input', function () {
        const query = this.value.trim();

        clearTimeout(debounceTimeout);

        if (query.length === 0) {
            resultsDiv.style.display = 'none';
            resultsDiv.innerHTML = '';
            return;
        }

        debounceTimeout = setTimeout(() => {
            fetch(`search_users.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        resultsDiv.innerHTML = '<div style="padding:10px;">لا يوجد نتائج</div>';
                    } else {
                        resultsDiv.innerHTML = data.map(user => `
                            <div onclick="window.location.href='profile.php?user=${encodeURIComponent(user.username)}'">
                                <img src="${user.profile_picture ? user.profile_picture : 'default-profile.png'}" alt="صورة المستخدم" />
                                <strong>${user.username}</strong> — ${user.first_name} ${user.last_name}
                            </div>
                        `).join('');
                    }
                    resultsDiv.style.display = 'block';
                })
                .catch(err => {
                    console.error('خطأ في جلب نتائج البحث:', err);
                    resultsDiv.style.display = 'none';
                });
        }, 300);
    });

    // إخفاء النتائج عند النقر خارج مربع البحث أو النتائج
    document.addEventListener('click', function (e) {
        if (!resultsDiv.contains(e.target) && e.target !== searchBox) {
            resultsDiv.style.display = 'none';
        }
    });

    // منع السلوك الافتراضي عند الضغط على Enter داخل مربع البحث
    searchBox.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
    const toggle = document.querySelector('.dropdown-toggle');
    const menu = document.getElementById('dropdown-menu');

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    });

    document.addEventListener('click', function () {
        menu.style.display = 'none';
    });
});
