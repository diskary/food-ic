<?php


namespace Tests\AppBundle\Entity;


use AppBundle\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /**
     * @dataProvider pricesForFood
     */
    public function testcomputeTVAFoodProduct($price, $expectedTVA)
    {
        $product = new Product('Un produit', Product::FOOD_PRODUCT,$price);

        $this->assertSame($expectedTVA, $product->computeTVA());
    }

    public function testcomputeTVAOtherProduct()
    {
        $product = new Product('un second produit', 'autre', 15);

        $this->assertSame(2.94, $product->computeTVA());
    }

    public function testNegativePriceComputeTVA()
    {
        $product = new Product('Un produit', Product::FOOD_PRODUCT,-20);

        $this->expectException('LogicException');

        $product->computeTVA();
    }

    public function pricesForFood()
    {
        return [
            
            [0,0.0],
            [20,1.1],
            [100,5.5]
        ];
    }
}