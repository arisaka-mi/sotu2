<script>
document.addEventListener('DOMContentLoaded', () => {

    const commentModal = document.getElementById('commentModal');
    const closeCommentModal = document.getElementById('closeCommentModal');
    const commentList = document.getElementById('modalCommentsArea');
    const commentForm = document.getElementById('commentForm');
    const commentPostId = document.getElementById('modalPostIdComment');
    const commentTextarea = document.getElementById('commentTextarea');
    const parentCmtId = document.getElementById('parentCmtId');
    const cancelReplyBtn = document.getElementById('cancelReplyBtn');
    const replyInfo = document.getElementById('replyInfo');
    const replyToName = document.getElementById('replyToName');
    const cancelReplyTop = document.getElementById('cancelReplyTop');

    let lastPageScrollY = window.scrollY;

    /* ===== モーダルを閉じる共通処理 ===== */
    function closeCommentModalFunc() {
        commentModal.style.display = 'none';
        commentList.innerHTML = '';
        cancelReply();
    }

    /* ===== ページスクロールのみで閉じる ===== */
    window.addEventListener('scroll', () => {
        // モーダルが閉じているなら何もしない
        if (commentModal.style.display !== 'flex') return;

        // ページスクロールが発生したら閉じる
        if (Math.abs(window.scrollY - lastPageScrollY) >= 15) {
            closeCommentModalFunc();
        }

        lastPageScrollY = window.scrollY;
    });

    /* ===== コメントボタン ===== */
    document.querySelectorAll('.comment-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();

            const postDiv = btn.closest('.post');
            const postId = postDiv.dataset.postId;

            commentPostId.value = postId;
            commentModal.style.display = 'flex';

            lastPageScrollY = window.scrollY; // ★ 開いた瞬間の位置を記録
            loadComments(postId);
        });
    });

    /* ===== いいねボタン ===== */
    document.querySelectorAll('.like-btn').forEach(likeBtn => {
        likeBtn.addEventListener('click', async () => {
            const likeIcon = likeBtn.querySelector('.like-icon');
            const modalLikes = likeBtn.querySelector('span'); // ★ 修正
            const postId = likeBtn.dataset.postId;

            try {
                const formData = new FormData();
                formData.append('post_id', postId);

                const res = await fetch('../home/toggle_like.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.status !== 'ok') {
                    alert('いいね処理に失敗しました');
                    return;
                }

                // アイコン切り替え
                if (data.liked) {
                    likeIcon.src = "../search/img/like_edge_2.PNG";
                    likeIcon.dataset.liked = "1";
                } else {
                    likeIcon.src = "../search/img/like_edge.PNG";
                    likeIcon.dataset.liked = "0";
                }

                // ★ 正しいいいね数を表示
                modalLikes.textContent = data.like_count;

            } catch (e) {
                console.error(e);
                alert('通信エラーが発生しました');
            }
        });
    });

    /* ===== 閉じるボタン ===== */
    closeCommentModal.addEventListener('click', closeCommentModalFunc);

    /* ===== 返信キャンセル ===== */
    function cancelReply(){
        parentCmtId.value = '';
        commentTextarea.placeholder = 'コメントを書く...';
        replyInfo.style.display = 'none';
        document.querySelectorAll('.comment-item')
            .forEach(c => c.classList.remove('reply-target'));
    }

    cancelReplyBtn.onclick = cancelReply;
    cancelReplyTop.onclick = cancelReply;

    /* ===== コメント取得 ===== */
    function loadComments(postId){
        fetch(`../home/add_comment.php?post_id=${postId}`)
            .then(res => res.text())
            .then(html => {
                commentList.innerHTML = html;
                attachReplyButtons();
            });
    }

    /* ===== 返信ボタン ===== */
    function attachReplyButtons(){
        document.querySelectorAll('.reply-btn').forEach(btn => {
            btn.onclick = () => {
                parentCmtId.value = btn.dataset.parentId;
                replyToName.textContent = btn.dataset.userName;
                replyInfo.style.display = 'flex';
                commentTextarea.placeholder = `@${btn.dataset.userName} に返信`;
                commentTextarea.focus();
            };
        });
    }

    /* ===== コメント送信 ===== */
    commentForm.addEventListener('submit', e => {
        e.preventDefault();
        if (!commentTextarea.value.trim()) return;

        const data = new URLSearchParams({
            post_id: commentPostId.value,
            comment: commentTextarea.value
        });

        if (parentCmtId.value) {
            data.append('parent_cmt_id', parentCmtId.value);
        }

        fetch('../home/add_comment.php', { method:'POST', body:data })
            .then(() => {
                commentTextarea.value = '';
                cancelReply();
                loadComments(commentPostId.value);
            });
    });

    /* ===== Enter送信 ===== */
    commentTextarea.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            commentForm.requestSubmit();
        }
    });

});
</script>