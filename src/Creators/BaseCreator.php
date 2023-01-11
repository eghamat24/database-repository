<?php

namespace Nanvaie\DatabaseRepository\Creators;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Nanvaie\DatabaseRepository\Commands\BaseCommand;
use Nanvaie\DatabaseRepository\Utility;
use function Nanvaie\DatabaseRepository\Commands\config;

class BaseCreator extends BaseCommand
{
    use Utility;
    private $creator;
    private null|string $choice=null;
    const ALL_OPTIONS = ['Current','New','Always keep current','Always replace with new'];

    public function __construct(IClassCreator $creator)
    {
        $this->creator = $creator;
    }

    public function createClass(string $filenameWithPath,BaseCommand $command):string
    {
        $attributesArray = $this->creator->createAttributs();
        $functionsArray = $this->creator->createFunctions();
        $usesArray = $this->creator->createUses();

        $specificPattern = "/(?<accessor>public|private|protected)\sfunction\s+(?<name>\w+)\s*\((?<param>[^\)]*)\)\s*:?\s*(?<return>.{0,100})\s*(?<body>\{(?:[^{}]+|(?&body))*\})/";
        $functionsArray = $this->checkDiffrence($filenameWithPath,$functionsArray,$command,$specificPattern);
        $generalPattern = "/class\s*[^$]*\{(?<main_part>[^}]*)((public|protected|private) function |})/isU";
        $specificPattern = '/(public|protected|private) [^\s]* \$*(?<name>[^\s;\=]*)\s*[^;]*;/is';
        $attributesArray = $this->checkDiffrence($filenameWithPath,$attributesArray,$command,$specificPattern,$generalPattern);

        $attributes = implode('    ',$attributesArray);
        $functions = implode('    ',$functionsArray);
        $uses = implode(PHP_EOL,$usesArray);

        $basePath = __DIR__ . '/../../stubs/base.class.stub' ;
        $this->creator->baseContent = str_replace(['{{ Namespace }}', '{{ UseSection }}', '{{ ClassName }}', '{{ ExtendSection }}', '{{ Parameters }}', '{{ Functions }}'],
            [
                $this->creator->getNameSpace(),
                $uses,
                $this->creator->getClassName(),
                $this->creator->getExtendSection(),
                $attributes,
                $functions
            ],
            file_get_contents($basePath));

        return $this->creator->baseContent;
    }

    public function checkDiffrence(string $filenameWithPath,array $newParamsArray,BaseCommand $command,string $specificPattern,string $generalPattern='/(?<main_part>.*)/is'): array
    {
        if (file_exists($filenameWithPath)) {
            $file = file_get_contents($filenameWithPath);
            if(preg_match($generalPattern, $file, $matches)){
                if(preg_match_all($specificPattern,$matches['main_part'],$attributMatches)){
                    for ($i=0; $i<count($attributMatches['name']);$i++){
                        if(array_search($this->getChoice(),self::ALL_OPTIONS)<2)
                            $this->setChoice(null);
                        if(!isset($newParamsArray[$attributMatches['name'][$i]])){
                            $newParamsArray[$attributMatches['name'][$i]] = '';
                        }
                        $attr = $newParamsArray[$attributMatches['name'][$i]];

                        if(preg_replace('/\s+/', '', $attr)===preg_replace('/\s+/', '', $attributMatches[0][$i])){
                            $command->info("There is no diffrence between '". $attributMatches['name'][$i] ."' ");
                        }else{
                            $command->warn("WARN: '". $attributMatches['name'][$i]."'s are not the same");
                            if(is_null($this->getChoice()) && array_search($this->getChoice(),self::ALL_OPTIONS)<2){
    //                            $command->table( ['Current','new'], [['Current'=>trim($attributMatches[0][$i]),'New'=>trim($attr)]],'default');
                                $command->line("######################## CURRENT #########################",'fg=magenta');
                                $command->line(trim($attributMatches[0][$i]),'fg=magenta');
                                $command->line("##########################################################",'fg=magenta');
                                $command->line(" ",'fg=magenta');
                                $command->line("########################## NEW ###########################",'fg=cyan');
                                $command->line(trim($attr),'fg=cyan');
                                $command->line("##########################################################",'fg=cyan');
                                $this->setChoice($command->choice('Choose one version',self::ALL_OPTIONS,0));
                                if(array_search($this->getChoice(),self::ALL_OPTIONS)%2==0 ){
                                    $newParamsArray[$attributMatches['name'][$i]]=trim($attributMatches[0][$i]).PHP_EOL;
                                    $command->warn("Action: Current version selected for '". $attributMatches['name'][$i] ."', ");
                                }
                            }elseif (array_search($this->getChoice(),self::ALL_OPTIONS)==2){
                                $newParamsArray[$attributMatches['name'][$i]]=trim($attributMatches[0][$i]).PHP_EOL;
                                $command->warn("Action: Current version selected for '". $attributMatches['name'][$i] ."', ");
                            }else{
                                $command->warn("Action: New version replaced for '". $attributMatches['name'][$i] ."', ");
                            }
                        }
                    }
                }
            }
        }
        return $newParamsArray;
    }
}
