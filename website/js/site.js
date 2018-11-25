function checkLength10(elem) {
  if (elem.value.length > 10) {
    elem.value = elem.value.substring(0, 10);
  }
}

function doSubmit(e) {
  var saveUser = document.getElementById("remember_username").checked;
  if (saveUser) {
    var username = document.getElementById("username").value;
    localStorage.setItem("username", username);
  }
}

function doPageLoad(e) {
  var username = localStorage.getItem("username");
  if (username) {
    document.getElementById("remember_username").checked = true;
    document.getElementById("username").value = username;
  }
}


document.getElementById("searchbtn").onclick = function() {
  location.href = "search.php";
}

// event listeners
window.addEventListener("load", doPageLoad, false)
document.getElementById("usernameForm").addEventListener("submit", doSubmit, false);
