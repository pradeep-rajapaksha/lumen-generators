<?php namespace Wn\Generators\Commands;


class ModelCommand extends BaseCommand {

	protected $signature = 'wn:model
        {name : Name of the model}
        {--fillable= : the fillable fields of the model}
        {--dates= : date fields of the model}
        {--has-many= : on-to-many relationships of the model}
        {--path=app : where to store the model php file}';

	protected $description = 'Generates a model class for a RESTfull resource';

    protected $fields = [];

    public function handle()
    {
        $name = $this->argument('name');
        $path = $this->option('path');

        $content = $this->getTemplate('model')
            ->with([
                'name' => $name,
                'namespace' => $this->getNamespace(),
                'fillable' => $this->getAsArrayFields('fillable'),
                'dates' => $this->getAsArrayFields('dates'),
                'relations' => $this->getRelations()
            ])
            ->get();

        $this->save($content, "./{$path}/{$name}.php");

        $this->info("Model {$name} Generated !");
    }

    protected function getAsArrayFields($arg, $isOption = true)
    {
    	$arg = ($isOption) ? $this->option($arg) : $this->argument($arg);
        if(is_string($arg)){
        	$arg = explode(',', $arg);
        } else {
            $arg = [];
        }
        return implode(', ', array_map(function($item){
            return '"' . $item . '"';
        }, $arg));
    }

    protected function getNamespace()
    {
    	return str_replace(' ', '\\', ucwords(str_replace('/', ' ', $this->option('path'))));
    }
	
    protected function getRelations()
    {
        $relations = array_merge([], 
            $this->getRelationsByType('hasMany', 'has-many')
        );

        return implode("\n\n", $relations);
    }

    protected function getRelationsByType($type, $option)
    {
        $relations = [];
        $option = $this->option($option);
        if($option){
            $parser = $this->getArgumentParser('relations');
            $template = $this->getTemplate('model/relation');
            $items = $parser->parse($option);
            foreach ($items as $item) {
                $item['type'] = $type;
                if(! $item['model']){
                    $item['model'] = $this->getNamespace() . '\\' . ucwords(str_singular($item['name']));
                } else if(strpos($item['model'], '\\') === false ){
                    $item['model'] = $this->getNamespace() . '\\' . $item['model'];
                }
                $relations[] = $template->with($item)->get();
            }
        }
        return $relations;
    }
}