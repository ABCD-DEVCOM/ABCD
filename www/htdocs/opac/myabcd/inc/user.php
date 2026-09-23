<?php

function MenuFinalUser() {
    global $arrHttp, $msgstr, $db_path, $valortag, $lista_bases, $dataarr;
    ?>
           
                        <div class="sectionTitle">
                            <h2><?php echo $msgstr["generaldata"]; ?></h2>
                        </div>
                           <div class="card mb-5 table-responsive">
                            <table class="table table-striped">
                                <tr>
                                    <td rowspan="4" width="150">
                                        <figure class="figure">
                                        <img width="150" src="../viewer/show_image.php?image=/images/<?php echo $_SESSION['user_photo']; ?>&base=users" class="figure-img img-fluid rounded" alt="...">
                                        <figcaption class="figure-caption"><a href="#">Change pic</a></figcaption>
                                        </figure>
                                    </td>
                                    <td rowspan="4">&nbsp;</td>
                                    <td><?php echo $msgstr["myuserid"]; ?></td>
                                    <td><?php echo $dataarr["id"] ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo $msgstr["name"]; ?></td>
                                    <td><?php echo $dataarr["name"] ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo $msgstr["userclass"]; ?></td>
                                    <td><?php echo $dataarr["userClass"] ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo $msgstr["datelimit"]; ?></td>
                                    <td><?php echo fechaAsString($dataarr["expirationDate"]); ?></td>
                                </tr>
                            </table>
</div>

<?php } //MenuFinalUser()?>