
<?php

        $def = parse_ini_file($db_path."abcd.def");
        //print_r($def);
        ?>



  <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <p class="col-md-4 mb-0 text-muted">&copy; 2022 
             <a href="<?php 
            if (isset($def["INSTITUTION_URL"])) {
                echo $def["INSTITUTION_URL"];
            } else {
                echo "//abcd-community.org";
            }?>" target="_blank">
            <?php 
                function randomName() {
                    $names = array(
                        'Automatisaci&oacute;n de Bibliot&eacute;cas y Centros de Documentaci&oacute;n',
                        'Automation des Biblioth&eacute;ques et Centres de Documentacion',
                        'Automatiza&ccedil;&atilde;o das Bibliotecas e dos Centros de Documenta&ccedil;&atilde;o',
                        'Automatisering van Bibliotheken en Centra voor Documentatie',
                        // and so on
                    );
                    return $names[rand ( 0 , count($names) -1)];
                }
            if (isset($def["INSTITUTION_NAME"])) {
                echo $def["INSTITUTION_NAME"]; 
            } else {
                echo "ABCD | ".randomName();
            }?>
        </a>
</p>

    <a href="/" class="col-md-4 d-flex align-items-center justify-content-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
      <img class="bi me-2" width="40" height="32" src="/assets/images/favicons/favicon.svg" alt="">
    </a>

    <ul class="nav col-md-4 justify-content-end">
      <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Home</a></li>
      <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Reserves</a></li>
      <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Search</a></li>
      <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Library</a></li>
      <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">About</a></li>
    </ul>
  </footer>

</div>


<!-- Modal -->
<div class="modal fade" id="abcdModal" tabindex="-1" aria-labelledby="abcdModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-body">
        <div class="ratio ratio-16x9">
            <iframe src=""  name="CheckOK"></iframe>
        </div>
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" OnClick="document.location.reload(true);">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js" ></script>

<script>
	document.cookie = "user=<?php echo $arrHttp["login"]?>; SameSite=None; Secure; expires=Thu, 18 Dec 2023 12:00:00 UTC; path=/";
</script>

</body>
</html>