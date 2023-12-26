<?php

function getWeights($odds, $profit = 0, $precision = 10){
    $weights = [];
    $totalWeights = 0;
    foreach($odds as $key => $value){
        $weights[$key] = 1;
        $totalWeights += $weights[$key];
    }
    $criterion = true;
    foreach($odds as $key => $value){
        $criterion = $criterion && ($weights[$key] * $odds[$key] >= $totalWeights + $profit);
    }
    $iterations = 0;
    while($criterion === false){
        $criterion = true;
        foreach($odds as $key => $value){
            if($weights[$key] * $odds[$key] < $totalWeights + $profit){
                $weights[$key] +=1;
                $totalWeights += 1;
            }
            $criterion = $criterion && ($weights[$key] * $odds[$key] >= $totalWeights + $profit);
        }
        $iterations ++;
        if($iterations == $precision) {
            $failed = [];
            foreach($odds as $key => $value) $failed[$key] = 0;
            return $failed;
        }
    }
    return $weights;
}

if(!isset($argv[1])) die("Race Date Not Entered!!\n");

$step = "winbets";
$raceDate = trim($argv[1]);
$currentDir = __DIR__ . DIRECTORY_SEPARATOR . $raceDate;

$allRacesRunners = include($currentDir . DIRECTORY_SEPARATOR . "1.php");
$allRacesOdds = include($currentDir . DIRECTORY_SEPARATOR . "getodds.php");
$history = include(__DIR__ . DIRECTORY_SEPARATOR . "triohistory.php");
$outFile = $currentDir . DIRECTORY_SEPARATOR . "$step.php";

$totalRaces = count($allRacesRunners);

$outtext = "<?php\n\n";
$outtext .= "return [\n";

for ($raceNumber = 1; $raceNumber <= $totalRaces; $raceNumber++) {
    if(!isset($allRacesRunners[$raceNumber])) continue;
    $runners = explode(", ", $allRacesRunners[$raceNumber]['Runners']);
    $favorite = $runners[0];
    $raceData = $history[$raceNumber][$favorite];
    $racetext = "";
   
    $racetext .= "\t'$raceNumber' => [\n";
    $racetext .= "\t\t/**\n";
    $racetext .= "\t\tRace $raceNumber\n";
    $racetext .= "\t\t*/\n";
    $racetext .= "\t\t'Favorite'    =>  '$favorite',\n";   
    $trio = $raceData['trio'];
    if(!empty($trio)){
        $allTrioValues = [];
        $TrioText = "[";
        $someCounter = 0;
        $someLength = count($trio);
        foreach($trio as $trioItem){
            $allTrioValues = array_values(array_unique(array_merge($allTrioValues, $trioItem)));
            $TrioText .= "[" . implode(", ", $trioItem) . "]";
            $someCounter ++;
            if($someCounter < $someLength) $TrioText .= ", ";
        }
        $TrioText .= "]";
        //Sort  allTrioValues by odds
        $qplsOdds = [];
        foreach($allTrioValues as $iIndex){
            if(isset($allRacesOdds[$raceNumber][$iIndex])) $qplsOdds[$iIndex] = $allRacesOdds[$raceNumber][$iIndex];
        }
        asort($qplsOdds);
        $allTrioValues = array_keys($qplsOdds);
        $racetext .= "\t\t'trio'        =>  $TrioText ,\n";
        $racetext .= "\t\t'trio values' =>  '" . implode(", ", $allTrioValues) . "',\n";
        $qin = array_slice($allTrioValues, 0, 6);
        $racetext .= "\t\t'qin' =>  '" . implode(", ", $qin) . "',\n";
        $weights = [];
        foreach($qin as $winner){
            $weights[$winner] = $allRacesOdds[$raceNumber][$winner];
        }
        $bets = getWeights($weights, 1);
        $racetext .= "\t\t'Win Bets'  =>  [\n";
        $total = 0;
        foreach($bets as $horse => $bet){
            $racetext .= "\t\t\t'$horse' => '" . 10 * $bet . " HKD',\n"  ;
            $total += 10 * $bet;
        }
        $racetext .= "\t\t],\n";
        $racetext .= "\t\t'Total Win Bets'  =>  '$total HKD',\n";
    }
    $racetext .= "\t],\n";
    $outtext .= $racetext;
}

$outtext .= "];\n";

file_put_contents($outFile, $outtext);
