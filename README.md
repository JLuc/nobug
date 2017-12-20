# nobug

#### Librairie de testing, debug et controle de qualité
- facile à utiliser car elle ne nécessite pas de devs "à part"
- tests automatiques et permanents ensuite, en tâche de fond

Cette librairie invite à expliciter le savoir implicite du dev sur un contexte PHP donné, sous la forme de contraintes assertives exprimées au sein même du code.

Lors du fonctionnement normal du code, ces déclarations permettent de s'assurer que le contexte d'exécution est conforme aux contraintes exprimées, et que les résultats sont conformes aux attentes.

Si ce n'est pas le cas, des logs sont générés, détaillant la pile d'exécution ainsi que le contexte du source PHP incriminé.

L'essentiel tient en 20 lignes.

La fonction principale est `debug_assert` : déclaration d'une contrainte (lors du codage) et vérification (lors de l'exécution) du bon respect de la contrainte exprimée.

Quand le code est au point, il ne doit y avoit aucune détection. Si ensuite le code d'une partie de code évolue, les assertions permettront de s'assurer que les contraintes décrivant les zones de bon fonctionnement des différentes fonctions restent bien respectées.

La librairie `nobug` peut être utilisée sur tout projet PHP, mais n'a été, intensément, utilisée que sur des projets SPIP 2 et SPIP 3.
