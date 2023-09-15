 <h1>我在測試Prompt Box</h1>
    <input class='prompttest' type="button" value="按我">
    <p class='show'></p>

    <script>
        var button = document.querySelector('.prompttest');
        var showtxt = document.querySelector('.show');

        function popup3(e) {
            var guest = window.prompt('您好!請輸入您的姓名', '迪迪希');
			 var guest = window.prompt('您好!請輸入您的姓名', '迪迪希');
            if (guest == null || "") {
                showtxt.innerHTML = '您已取消輸入'
            } else {
                showtxt.innerHTML = 'Good Day' + guest + '^^'
            }

        }
        button.addEventListener('click', popup3);
    </script>