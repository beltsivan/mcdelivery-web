function showLogin() {
        document.getElementById('loginModal').style.display = 'flex';
    }

    function closeLogin() {
        document.getElementById('loginModal').style.display = 'none';
    }

    // Close if user clicks outside the card
    window.onclick = function(event) {
        var modal = document.getElementById('loginModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }