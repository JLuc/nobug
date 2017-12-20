<?php

// Exemple de fonction spécialisée pour décrire une contrainte non élémentaire
//

// s'assurer que $a est un array de la forme ('id_objet' => 9, 'objet' = > "article")
// $ctx est un contexte optionnel permettant d'éclaircir le contexte
// et de documenter la contrainte
function nobug_array_objet_spip ($a, $ctx='') {
  ( nobug_assert (is_array($a), "$ctx : pas un tableau") 
   and  nobug_assert (count($a)==2, "$ctx : le tableau devrait avoir 2 éléments mais en a ".count($a)) 
   and  nobug_assert (isset($a['objet']), "$ctx : pas de clé objet") 
   and  nobug_assert (isset($a['id_objet']), "$ctx : pas de clé id_objet") 
   and  nobug_assert (isnumeric ($a['id_objet']), "$ctx : l'id_objet n'est pas numérique")
  // éventuellement encore : 
  // tester que $a['objet'] est bien une table spip déclarée
  // tester que l'objet $id_objet existe bien...  
  )
    or nobug_assert (false, "Valeur actuelle : print_r($a,1))
    or return false;
  return true;
}
