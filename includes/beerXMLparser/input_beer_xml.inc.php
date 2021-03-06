<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

//{{{ License
// +------------------------------------------------------------------------+
// | Input Beer XML - takes recipe objects from BeerXMLParser               |
// |                  and inserts recipes into database                     |
// | 							                                            |
// | NOTES - Augmented by Geoff Humphrey for use in BrewBlogger	2.3         |
// |         <brewmeister@brewblogger.net>                                  |
// |       - Added conversion variables based upon BB preferences           |
// |       - Beer XML standards are in Metric for weight/volume, C for temp |
// +------------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or          |
// | modify it under the terms of the GNU General Public License            |
// | as published by the Free Software Foundation; either version 2         |
// | of the License, or (at your option) any later version.                 |
// |                                                                        |
// | This program is distributed in the hope that it will be useful,        |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of         |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          |
// | GNU General Public License for more details.                           |
// |                                                                        |
// | You should have received a copy of the GNU General Public License      |
// | along with this program; if not, write to the Free Software            |
// | Foundation, Inc., 59 Temple Place - Suite 330,                         |
// | Boston, MA  02111-1307, USA.                                           |
// +------------------------------------------------------------------------+
// | Author: Oskar Stephens <oskar.stephens@gmail.com>	                    |
// +------------------------------------------------------------------------+
//}}}
include ('parse_beer_xml.inc.php');
//{{{ InputBeerXML
class InputBeerXML {
    public $recipes; 
    public $insertedRecipes;
    public $brewer;
    //{{{InputBeerXML
    function InputBeerXML($filename) {
        $this->brewer = $GLOBALS['loginUsername'];
        $this->recipes = new BeerXMLParser($filename);
    }
    //}}}
	
	//{{{ convertUnit()
    function convertUnit($value,$type){
	    include('../Connections/config.php');
		mysqli_select_db($brewing,$database_brewing);
		$query_pref_xml = "SELECT measWeight1,measTemp,measFluid2,measWeight2 FROM preferences";
		$pref_xml = mysqli_query($brewing,$query_pref_xml) or die(mysqli_error($brewing));
		$row_pref_xml = mysqli_fetch_assoc($pref_xml);
		$totalRows_pref_xml = mysqli_num_rows($pref_xml);
		
        switch($type){
            case "hopWeight";
				if ($row_pref_xml['measWeight1'] == "grams") return round($value * 1000, 2);
				else return round($value * 35.27396, 2);
			case "temperature":
				if ($row_pref_xml['measTemp'] == "F") return round (($value * 1.8) + 32, 0);
				else return round($value, 0); 
            case "volume":
				if ($row_pref_xml['measFluid2'] == "gallons") return round($value * 0.26417, 1);
				else return round($value, 1);
            case "weight":
                if ($row_pref_xml['measWeight2'] == "pounds") return round($value * 2.20462, 2); 
				else return round($value, 2);
            default:
            break;
        }
    }
    //}}}


    //{{{ insertRecipes
    function insertRecipes(){
        foreach($this->recipes->recipes as $recipe){
            $this->insertRecipe($recipe);
        }
        return $this->insertedRecipes;
    }
    //}}}

    //{{{ insertRecipe
    function insertRecipe($recipe){  // inserts into `recipes` DB table
	   include ('../includes/scrubber.inc.php');
        include('../Connections/config.php');
        mysqli_select_db($brewing,$database_brewing);
        $sqlQuery = "INSERT INTO recipes ";
        $fields = "(brewName";
        $values = " VALUES('" .  strtr($recipe->name, $html_string) . "'";
        $vf = array();
        $counter = array();
		//$vf["brewName"] = $this->strtr($recipe->name, $html_string);
        $vf["brewStyle"] = $recipe->style->name;
        $vf["brewSource"] = $recipe->brewer;
        $vf["brewYield"] = $this->convertUnit($recipe->batchSize, "volume");
        $vf["brewNotes"] = strtr($recipe->notes, $html_string);
        $vf["brewMethod"] = $recipe->type; 
        $counter["grain"] = 0;
        $counter["extract"] = 0;
        $counter["adjunct"] = 0;
        foreach($recipe->fermentables->fermentables as $fermentable){
            switch($fermentable->type){
                case "Grain":
                    $counter["grain"]++;
                    if($counter["grain"] <= 15){
                        $vf["brewGrain" . $counter["grain"]] = strtr($fermentable->name, $html_string);
						$vf["brewGrain" . $counter["grain"] . "Weight"] = $this->convertUnit($fermentable->amount,"weight"); 
						
					}                    
				break;
                case "Extract":
                    $counter["extract"]++;
                    if($counter["extract"] <= 5){
                        $vf["brewExtract" . $counter["extract"]] = strtr($fermentable->name, $html_string);
                        $vf["brewExtract" . $counter["extract"] . "Weight"] = $this->convertUnit($fermentable->amount,"weight"); 
                    }
                    break;
                case "Dry Extract":
                    $counter["extract"]++;
                    if($counter["extract"] <= 5){
                        $vf["brewExtract" . $counter["extract"]] = strtr($fermentable->name, $html_string);
                        $vf["brewExtract" . $counter["extract"] . "Weight"] = $this->convertUnit($fermentable->amount,"weight"); ;
                    }
                    break;
                case "Adjunct":
                    $counter["adjunct"]++;
                    if($counter["adjunct"] <= 9){
                        $vf["brewAddition" . $counter["adjunct"]] = strtr($fermentable->name, $html_string);
						$vf["brewAddition" . $counter["adjunct"] . "Amt"] = $this->convertUnit($fermentable->amount,"weight"); 
						}
                    break;
                case "Sugar":
                    $counter["adjunct"]++;
                    if($counter["adjunct"] <= 9){
                        $vf["brewAddition" . $counter["adjunct"]] = strtr($fermentable->name, $html_string);
                        $vf["brewAddition" . $counter["adjunct"] . "Amt"]  = $this->convertUnit($fermentable->amount,"weight"); 
                    }
                    break;
                default:
                    break;
            }
        }

        $counter["misc"] = 0;
        foreach($recipe->miscs->miscs as $misc){
            $counter["misc"]++;
            if($counter["misc"] <= 4){
                $vf["brewMisc" . $counter["misc"] . "Name"] = strtr($misc->name, $html_string);
                $vf["brewMisc" . $counter["misc"] . "Type"] = $misc->type;  // BeerXML differntiates between liquid and volume - BB 2.2 does not - item for future release
                $vf["brewMisc" . $counter["misc"] . "Use"] = $misc->useFor;
                $vf["brewMisc" . $counter["misc"] . "Time"] = round($misc->time, 0);
                $vf["brewMisc" . $counter["misc"] . "Amount"] = round($misc->amount, 2);  // Beer XML standard is kg or liters - will need to address in subsequent release
            }
        }

        $counter["hops"] = 0;
        foreach($recipe->hops->hops as $hop){
            $counter["hops"]++;
            if($counter["hops"] <= 15){
                $vf["brewHops" . $counter["hops"]] = strtr($hop->name, $html_string);
				$vf["brewHops" . $counter["hops"] . "Weight"] = $this->convertUnit($hop->amount,"hopWeight");
				$vf["brewHops" . $counter["hops"] . "IBU"] = $hop->alpha;
                $vf["brewHops" . $counter["hops"] . "Time"] = round($hop->time, 0);
                $vf["brewHops" . $counter["hops"] . "Use"] = $hop->use;
                $vf["brewHops" . $counter["hops"] . "Type"] = $hop->type;
                $vf["brewHops" . $counter["hops"] . "Form"] = $hop->form;
            }
        }

        $counter["yeast"] = 0;
        foreach($recipe->yeasts->yeasts as $yeast){
            $vf["brewYeast"] = strtr($yeast->name, $html_string);
            $vf["brewYeast" . "Man"] = $yeast->laboratory;
            $vf["brewYeast" . "Form"] = $yeast->form;
            $vf["brewYeast" . "Type"] = $yeast->type;
            if($yeast->amountIsWeight == "TRUE"){
                $vf["brewYeast" . "Amount"] = $this->convertUnit($yeast->amount,"weight"); 
            }else {
                $vf["brewYeast" . "Amount"] = $this->convertUnit($yeast->amount,"volume"); 
            }        
		}

        $vf["brewOG"] = $recipe->og; // changed_GH
        $vf["brewFG"] = $recipe->fg; // changed_GH
        // $vf["brewProcedure"] = $recipe->notes;
        if ($recipe->primaryAge != "") { $vf["brewPrimary"] = round($recipe->primaryAge, 0); }
        if ($recipe->primaryTemp != "") { $vf["brewPrimaryTemp"] = $this->convertUnit($recipe->primaryTemp,"temperature"); }
        if ($recipe->secondaryAge != "") { $vf["brewSecondary"] = round($recipe->secondaryAge, 0); }
		if ($recipe->secondaryTemp != "") { $vf["brewSecondaryTemp"] = $this->convertUnit($recipe->secondaryTemp,"temperature"); }
		if ($recipe->tertiaryAge != "") { $vf["brewTertiary"] = round($recipe->tertiaryAge, 0); }
        if ($recipe->tertiaryTemp != "") { $vf["brewTertiaryTemp"] = $this->convertUnit($recipe->tertiaryTemp,"temperature"); }
		if ($recipe->age != "") { $vf["brewAge"] = round($recipe->age, 0); }
        if ($recipe->ageTemp != "") { $vf["brewAgeTemp"] = $this->convertUnit($recipe->ageTemp,"temperature"); }
		if ($recipe->ibu != "") { $vf["brewBitterness"] = $recipe->ibu; }
        if ($recipe->estimatedColor != "") { $vf["brewLovibond"] = 0 . rtrim($recipe->estimatedColor," SRM"); }
        $vf["brewBrewerID"] = $GLOBALS['loginUsername']; 																// changed_GH to accomodate club edition
		
        foreach($vf as $field=>$value){
            $fields .= "," . $field;
            $values .= ",'" . $value . "'";
        }
		$fields .= ", brewArchive";
        $fields .= ")";
		$values .= ", 'N'";
        $values .= ")";
        $sqlQuery .= $fields . $values;
        mysqli_real_escape_string($brewing,$sqlQuery);
        $Result1 = mysqli_query($brewing,$sqlQuery) or die(mysqli_error($brewing));

        $this->insertedRecipes[mysqli_insert_id()] = $recipe->name;
        }
    //}}}

  //{{{ insertBlogs
    function insertBlogs(){
        foreach($this->recipes->recipes as $recipe){
            $this->insertBlog($recipe);
        }
        return $this->insertedRecipes;
    }

//}
//}}}


    //{{{ insertBlog
    function insertBlog($recipe){
	include ('../includes/scrubber.inc.php');
        include('../Connections/config.php');
        mysqli_select_db($brewing,$database_brewing);

        $sqlQuery = "INSERT INTO brewing ";
        $fields = "(brewName";
        $values = " VALUES('" .  strtr($recipe->name, $html_string) . "'";
        $vf = array();
        $counter = array();
        // $batchNumber = " SELECT brewBatchNum FROM `brewing` ORDER BY brewBatchNum DESC LIMIT 1 ";

        //$vf["brewName"] = $recipe->name;
        $vf["brewStyle"] = $recipe->style->name;
		$dateCheck = datecharcheck($recipe->date);
        if ($dateCheck == "true") $vf["brewDate"] = dateconvert($recipe->date, "4");
		elseif ($dateCheck == "4-") $vf["brewDate"] = $recipe->date;
		else $vf["brewDate"] = date("Y-m-d");
        $vf["brewYield"] = $this->convertUnit($recipe->batchSize, "volume");
        $vf["brewComments"] = strtr($recipe->notes, $html_string);
        $vf["brewMethod"] = $recipe->type; 
        $counter["grain"] = 0;
        $counter["extract"] = 0;
        $counter["adjunct"] = 0;
        foreach($recipe->fermentables->fermentables as $fermentable){
            switch($fermentable->type){
                case "Grain":
                    $counter["grain"]++;
                    if($counter["grain"] <= 15){
                        $vf["brewGrain" . $counter["grain"]] = strtr($fermentable->name, $html_string);
						$vf["brewGrain" . $counter["grain"] . "Weight"] = $this->convertUnit($fermentable->amount,"weight");
					}                    
				break;
                case "Extract":
                    $counter["extract"]++;
                    if($counter["extract"] <= 5){
                        $vf["brewExtract" . $counter["extract"]] = strtr($fermentable->name, $html_string);
                        $vf["brewExtract" . $counter["extract"] . "Weight"] = $this->convertUnit($fermentable->amount,"weight"); 
                    }
                    break;
                case "Dry Extract":
                    $counter["extract"]++;
                    if($counter["extract"] <= 5){
                        $vf["brewExtract" . $counter["extract"]] = strtr($fermentable->name, $html_string);
                        $vf["brewExtract" . $counter["extract"] . "Weight"] = $this->convertUnit($fermentable->amount,"weight"); ;
                    }
                    break;
                case "Adjunct":
                    $counter["adjunct"]++;
                    if($counter["adjunct"] <= 9){
                        $vf["brewAddition" . $counter["adjunct"]] = strtr($fermentable->name, $html_string);
						$vf["brewAddition" . $counter["adjunct"] . "Amt"] = $this->convertUnit($fermentable->amount,"weight"); 
						}
                    break;
                case "Sugar":
                    $counter["adjunct"]++;
                    if($counter["adjunct"] <= 9){
                        $vf["brewAddition" . $counter["adjunct"]] = strtr($fermentable->name, $html_string);
                        $vf["brewAddition" . $counter["adjunct"] . "Amt"]  = $this->convertUnit($fermentable->amount,"weight"); 
                    }
                    break;
                default:
                    break;
            }
        }

        $counter["misc"] = 0;
        foreach($recipe->miscs->miscs as $misc){
            $counter["misc"]++;
            if($counter["misc"] <= 4){
                $vf["brewMisc" . $counter["misc"] . "Name"] = strtr($misc->name, $html_string);
                $vf["brewMisc" . $counter["misc"] . "Type"] = $misc->type;  // BeerXML differntiates between liquid and volume - BB 2.2 does not - item for future release
                $vf["brewMisc" . $counter["misc"] . "Use"] = $misc->useFor;
                $vf["brewMisc" . $counter["misc"] . "Time"] = round($misc->time, 0);
                $vf["brewMisc" . $counter["misc"] . "Amount"] = round($misc->amount, 2);  // Beer XML standard is kg or liters - will need to address in subsequent release
            }
        }

        $counter["hops"] = 0;
        foreach($recipe->hops->hops as $hop){
            $counter["hops"]++;
            if($counter["hops"] <= 15){
                $vf["brewHops" . $counter["hops"]] = strtr($hop->name, $html_string);
				$vf["brewHops" . $counter["hops"] . "Weight"] = $this->convertUnit($hop->amount,"hopWeight");
				$vf["brewHops" . $counter["hops"] . "IBU"] = $hop->alpha;
                $vf["brewHops" . $counter["hops"] . "Time"] = round($hop->time, 0);
                $vf["brewHops" . $counter["hops"] . "Use"] = $hop->use;
                $vf["brewHops" . $counter["hops"] . "Type"] = $hop->type;
                $vf["brewHops" . $counter["hops"] . "Form"] = $hop->form;
            }
        }

        $counter["yeast"] = 0;
        foreach($recipe->yeasts->yeasts as $yeast){
            $vf["brewYeast"] = strtr($yeast->name, $html_string);
            $vf["brewYeast" . "Man"] = $yeast->laboratory;
            $vf["brewYeast" . "Form"] = $yeast->form;
            $vf["brewYeast" . "Type"] = $yeast->type;
            if($yeast->amountIsWeight == "TRUE"){
                $vf["brewYeast" . "Amount"] = $this->convertUnit($yeast->amount,"weight"); 
            } else {
                $vf["brewYeast" . "Amount"] = $this->convertUnit($yeast->amount,"volume"); 
            }        
		}
		
		
		
		$counter["mash"] = 0;
        //$vf["brewMashGrainWeight"] = $recipe->mash->
        $vf["brewMashGrainTemp"] = $this->convertUnit($recipe->mash->grainTemp,"temperature");
        $vf["brewMashTunTemp"] = $this->convertUnit($recipe->mash->tunTemp,"temperature");
        $vf["brewMashPH"] = $recipe->mash->ph;
        $vf["brewMashGrainWeight"] = $totalGrainWeight;
        $vf["brewMashType"] = "Infusion"; // this is hard coded because it is the most common and the beerXML spec does not mention it
        $vf["brewMashEquipAdjust"] = $recipe->mash->equipAdjust;  // FIELDS TO COMPLETE: spargeAmt
        $vf["brewMashSpargeTemp"] = $this->convertUnit($recipe->mash->spargeTemp,"temperature");
        $totalSpargeAmount = 0;
        foreach($recipe->mash->mashSteps as $mashStep){
            $counter["mash"]++;
            if($counter["mash"] <= 5){
                $vf["brewMashStep" . $counter["mash"] . "Name"] = strtr($mashStep->name, $html_string);
                $vf["brewMashStep" . $counter["mash"] . "Temp"] = $this->convertUnit($mashStep->stepTemp,"temperature");
                $vf["brewMashStep" . $counter["mash"] . "Time"] = $mashStep->stepTime;
                $vf["brewMashStep" . $counter["mash"] . "Desc"] = $mashStep->type;
                $totalSpargeAmount += $mashStep->infuseAmount;
            }
        }
        $vf["brewMashSpargAmt"] = round($this->convertUnit($totalSpargeAmount,"volume"),3);

        foreach($recipe->waters->waters as $water){
            $vf["brewWaterName"] = strtr($water->name, $html_string);
            $vf["brewWaterAmount"] = $water->amount;
            $vf["brewWaterCalcium"] = $water->calcium;
            $vf["brewWaterBicarb"] = $water->bicarbonate;
            $vf["brewWaterSulfate"] = $water->sulfate;
            $vf["brewWaterChloride"] = $water->chloride;
            $vf["brewWaterMagnesium"] = $water->magnesium;
            $vf["brewWaterPH"] = $water->ph;
            $vf["brewWaterNotes"] = $water->notes;
            $vf["brewWaterSodium"] = $water->sodium;
        }
		
		
		
		$vf["brewOG"] = $recipe->og; // changed_GH
        $vf["brewFG"] = $recipe->fg; // changed_GH
        $vf["brewComments"] = strtr($recipe->notes, $html_string);
        if ($recipe->primaryAge != "") { $vf["brewPrimary"] = round($recipe->primaryAge, 0); }
        if ($recipe->primaryTemp != "") { $vf["brewPrimaryTemp"] = $this->convertUnit($recipe->primaryTemp,"temperature"); }
        if ($recipe->secondaryAge != "") { $vf["brewSecondary"] = round($recipe->secondaryAge, 0); }
		if ($recipe->secondaryTemp != "") { $vf["brewSecondaryTemp"] = $this->convertUnit($recipe->secondaryTemp,"temperature"); }
		if ($recipe->tertiaryAge != "") { $vf["brewTertiary"] = round($recipe->tertiaryAge, 0); }
        if ($recipe->tertiaryTemp != "") { $vf["brewTertiaryTemp"] = $this->convertUnit($recipe->tertiaryTemp,"temperature"); }
		if ($recipe->age != "") { $vf["brewAge"] = round($recipe->age, 0); }
        if ($recipe->ageTemp != "") { $vf["brewAgeTemp"] = $this->convertUnit($recipe->ageTemp,"temperature"); }
		if ($recipe->ibu != "") { $vf["brewBitterness"] = $recipe->ibu; }
        if ($recipe->estimatedColor != "") { $vf["brewLovibond"] = 0 . rtrim($recipe->estimatedColor," SRM"); }
        $vf["brewBrewerID"] = $GLOBALS['loginUsername']; 
		if ($recipe->efficiency != "") { $vf["brewEfficiency"] = $recipe->efficiency; }
        if ($recipe->boilSize != "") { $vf["brewPreBoilAmt"] = $this->convertUnit($recipe->boilSize,"volume"); }															// changed_GH to accomodate club edition
		
        foreach($vf as $field=>$value){
            $fields .= "," . $field;
            $values .= ",'" . $value . "'";
        }

        $fields .= ", brewArchive";
        $fields .= ")";
		$values .= ", 'N'";
        $values .= ")";
        $sqlQuery .= $fields . $values;
        mysqli_real_escape_string($brewing,$sqlQuery);
        $Result1 = mysqli_query($brewing,$sqlQuery) or die(mysqli_error($brewing));

        $this->insertedRecipes[mysqli_insert_id()] = $recipe->name;
    }
  }

//}}}
//}}}
//}}}
//}}}

?>

