// 超シンプルなライトボックス
document.addEventListener('click', e => {
    const img = e.target.closest('img[data-lightbox]');
    if (!img) return;
    e.preventDefault();

    const overlay = document.createElement('div');
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,.9);display:grid;place-items:center;z-index:9999;';
    const big = document.createElement('img');
    big.src = img.src.replace('/thumbs/','/uploads/').replace('_t.','.');
    big.style.maxWidth = '90vw';
    big.style.maxHeight = '90vh';
    overlay.appendChild(big);
    overlay.addEventListener('click', () => document.body.removeChild(overlay));
    document.body.appendChild(overlay);
});

// view.php の Back ボタン専用処理
document.addEventListener('DOMContentLoaded', () => {
    const backLink = document.getElementById('backLink');
    if (backLink) {
      backLink.addEventListener('click', function (e) {
        if (window.history.length > 1) {
          e.preventDefault();
          window.history.back();
        } else if (document.referrer) {
          e.preventDefault();
          location.href = document.referrer;
        }
      });
    }
  });
  